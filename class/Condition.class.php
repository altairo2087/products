<?php
/**
 * Class Condition
 * Условия формирования цен
 */
abstract class Condition {
    /**
     * Рассчет текущего условия в корзине
     * @param Cart $cart
     * @return mixed
     */
    abstract public function calc(Cart $cart);
}

/**
 * Class ConjCondition
 * Условие вида:
 * "Если одновременно выбраны какие-то продукты, то их суммарная стоимость изменяется на какой-то процент"
 */
class ConjCondition extends Condition {
    private $products = array();
    private $productsCount;
    private $percent;
    private $sum=0;

    public function __construct(array $products, $percent) {

        if (is_numeric($percent))
            $this->percent = $percent;
        else
            throw new Exception("Некорректный параметр процента в условии");

        foreach ($products as $product) {
            if (Price::checkProduct($product)) {
                if (!in_array($product, $this->products)) {
                    $this->products[] = $product;
                    $this->sum += Price::getProductPrice($product);
                }
                else
                    throw new Exception("Продукт не может дублироваться в условии");
            }
        }

        $this->productsCount = count($this->products);

        if (!$this->productsCount)
            throw new Exception("В условии должен быть хотя бы один продукт");
    }

    public function calc(Cart $cart) {

        // количество совпадений
        $num = 0;
        /**
         * Собранные группы
         */
        $groups = array();

        foreach ($cart->_calculatedProducts as $i=>$product) {
            $finded = false;
            // ищем продукт
            if (in_array($product['product'], $this->products)) {
                // если продукт еще не участвовал в скидках
                if (!$product['calculated']) {
                    // здесь собираем группы товаров
                    if (count($groups)) {
                        foreach($groups as $index=>$group) {
                            // помещаем продукт в группу
                            if (!in_array($product['product'],$group)) {
                                $groups[$index][$i] = $product['product'];
                                // группа собрана
                                if ($this->productsCount==count($groups[$index])) {
                                    ++$num;
                                    foreach($groups[$index] as $key=>$val) {
                                        $cart->_calculatedProducts[$key]['calculated'] = true;
                                        $cart->_calculatedProducts[$key]['price'] += $cart->_calculatedProducts[$key]['price']/100*$this->percent;
                                    }
                                }
                                $finded = true;
                                break;
                            }
                        }
                    }

                    // если продукт подходит, но не в группе, создаем группу
                    if (!$finded)
                        $groups[][$i] = $product['product'];
                }
            }
        }

        return $cart->getCalculatedSum() + ($this->sum/100 * $this->percent ) * $num;
    }
}

/**
 * Class DisjCondition
 * Условие вида:
 * "Если выбран товар А и любой из K,L,M, то цены K,L,M уменьшаются"
 */
class DisjCondition extends Condition {
    private $required = array();
    private $optional = array();
    private $percent;

    public function __construct($required,$optional,$percent) {
        if (is_numeric($percent))
            $this->percent = $percent;
        else
            throw new Exception("Некорректный параметр процента в условии");

        foreach ($required as $product) {
            if (Price::checkProduct($product)) {
                if (!in_array($product, $this->required) && !in_array($product, $this->optional)) {
                    $this->required[] = $product;
                }
                else
                    throw new Exception("Продукт не может дублироваться в условии");
            }
        }

        if (!count($this->required))
            throw new Exception("В условии должен быть хотя бы один продукт");

        foreach ($optional as $product) {
            if (Price::checkProduct($product)) {
                if (!in_array($product, $this->required) && !in_array($product, $this->optional)) {
                    $this->optional[] = $product;
                }
                else
                    throw new Exception("Продукт не может дублироваться в условии");
            }
        }

        if (!count($this->optional))
            throw new Exception("В условии должен быть хотя бы один продукт");
    }

    public function calc(Cart $cart) {

        // проверяем выбраны ли обязательные продукты
        $allSearched = false;
        $cnt = count($this->required);
        $group = array();
        foreach($cart->getProducts() as $product) {
            if (in_array($product, $this->required)) {
                if (!in_array($product,$group)) {
                    $group[] = $product;
                    if ($cnt==count($group)) {
                        $allSearched = true;
                        break;
                    }
                }
            }
        }

        // если выбраны высчитываем скидки
        if ($allSearched) {
            $sum = 0;
            foreach ($cart->_calculatedProducts as $i=>$product) {
                if (in_array($product['product'], $this->optional)) {
                    if (!$product['calculated']) {
                        $cart->_calculatedProducts[$i]['calculated'] = true;
                        $cart->_calculatedProducts[$i]['price'] += $cart->_calculatedProducts[$i]['price']/100*$this->percent;
                        // высчитываем из каждого такого продукта скидку
                        $sum += Price::getProductPrice($product['product']) / 100 * $this->percent;
                    }
                }
            }
        }

        return $cart->getCalculatedSum() + $sum;
    }
}

/**
 * Class CountCondition
 * Условие вида:
 * "Если выбраны более n продуктов, то скидка на весь заказ m процентов"
 */
class CountCondition extends Condition {
    private $conditions = array();
    private $exceptions = array();

    public function __construct() {
        foreach(func_get_args() as $arg) {
            if (!is_numeric($arg[0]) && !is_numeric($arg[1]))
                throw new Exception("Некорректные параметры условия");
            $this->conditions[] = $arg;
        }

        if (!count($this->conditions))
            throw new Exception("В условии отсутствуют необходимые параметры");

        uasort($this->conditions, function($a,$b){
           return  $b[0]-$a[0];
        });
    }

    /**
     * Продукты не участвующие в скидке
     * @param $products
     */
    public function addExceptions(array $products) {
        foreach ($products as $product) {
            if (Price::checkProduct($product))
                $this->exceptions[] = $product;
        }
    }

    public function calc(Cart $cart) {
        $count = 0;
        foreach($cart->_calculatedProducts as $index=>$product) {
            if (!in_array($product['product'],$this->exceptions)) {
                ++$count;
            }
        }

        $sum = 0;
        foreach ($this->conditions as $condition) {
            if ($condition[0]<=$count) {

                foreach($cart->_calculatedProducts as $index=>$product) {
                    if (!in_array($product['product'],$this->exceptions)) {
                        $sum += $product['price'];
                    }
                }
                $sum = $sum / 100 * $condition[1];
                break;
            }
        }

        return $cart->getCalculatedSum() + $sum;
    }
}