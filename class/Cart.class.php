<?php
/**
 * Class Cart
 * Корзина с товарами
 */
class Cart {

    /**
     * Продукты в корзине, с начальными ценами
     * @var array
     */
    private $products = array();

    /**
     * Условия формирования цен (скидки, наценки и т.д.)
     * @var array
     */
    private $conditions = array();

    /**
     * Сумма товаров без учета дополнительных условий
     * @var int
     */
    private $originalSum;

    /**
     * Сумма товаров со всеми условиями формирования
     * @var int
     */
    private $calculatedSum;

    /**
     * Служебный массив, нужен для сохранения результатов вычислений условий
     * Используется в условиях, чтобы видеть данные рассчета предыдущих условий
     * Хранит данные в формате
     * array(
     *     0 => array(
     *         'product' => 'A',
     *         'price'   => 10,
     *         'calculated' => false,
     *     ),
     * )
     * @var array
     */
    public $_calculatedProducts = array();


    /**
     * @param $name
     */
    public function __construct($name="") {
        if ($name)
          $this->addProduct($name);
    }

    /**
     * Добавление продукта в корзину
     * Можно добавить по одному Cart->addProduct("Продукт");
     * Можно передать массив Cart->addProduct(array("Продукт1","Продукт2"));
     * @param $name
     */
    public function addProduct($name) {
        $this->originalSum = null;
        $this->calculatedSum = null;
        $this->_calculatedProducts = array();

        if (is_array($name)) {
            foreach ($name as $value) {
                if (Price::checkProduct($value))
                    $this->products[] = $value;
            }
        }
        else {
            if (Price::checkProduct($name))
                $this->products[] = $name;
        }
    }

    /**
     * Добавление условия рассчета цены
     * @param Condition $condition
     */
    public function addCondition(Condition $condition) {
        $this->calculatedSum = null;
        $this->_calculatedProducts = array();

        $this->conditions[] = $condition;
    }

    /**
     * Список продуктов в корзине
     */
    public function getProducts() {
        return $this->products;
    }

    public function getCalculatedSum() {
        return $this->calculatedSum;
    }


    /**
     * Чистая сумма без скидок, нааценок и других условий
     */
    public function getOriginalSum() {

        if ($this->originalSum===null) {
            $this->originalSum = 0;
            foreach ($this->products as $product) {
                $this->originalSum += Price::getProductPrice($product);
            }
        }

        return $this->originalSum;
    }

    /**
     * Сумма со всеми скидками
     */
    public function getFinalSum() {

        if ($this->calculatedSum===null) {
            $this->calculatedSum = $this->getOriginalSum();
            if (count($this->conditions)) {
                $this->_calculatedProducts = array();
                foreach ($this->products as $product) {
                    $this->_calculatedProducts[] = array(
                      'product' => $product,
                      'price' => Price::getProductPrice($product),
                      'calculated' => false,
                    );
                }
                foreach ($this->conditions as $condition) {
                    $this->calculatedSum = $condition->calc($this);
                }
            }
        }

        return $this->calculatedSum;
    }
}