<?php
header("Content-type: text/html;charset=utf-8");

include "class/Price.class.php";
include "class/Cart.class.php";
include "class/Condition.class.php";

$cart = new Cart();

//----- добавляем продукты
$cart->addProduct(array("A","A","B","D","E","E","F","G"));
$cart->addProduct(array("K","L"));
$cart->addProduct("M");
$cart->addProduct(array("B","A","C","B","B"));



//----- добавляем условия рассчета цен
// 10. Каждый товар может участвовать только в одной скидке. Скидки применяются последовательно в порядке описанном выше.

// 1. Если одновременно выбраны А и B, то их суммарная стоимость уменьшается на 10% (для каждой пары А и B)
$cart->addCondition(
  new ConjCondition(array("A","B"), -10)
);
// 2. Если одновременно выбраны D и E, то их суммарная стоимость уменьшается на 5% (для каждой пары D и E)
$cart->addCondition(
  new ConjCondition(array("D","E"), -5)
);
// 3. Если одновременно выбраны E,F,G, то их суммарная стоимость уменьшается на 5% (для каждой тройки E,F,G)
$cart->addCondition(
  new ConjCondition(array("E","F","G"), -5)
);

// 4. Если одновременно выбраны А и один из [K,L,M], то стоимость выбранного продукта уменьшается на 5%
$cart->addCondition(
  new DisjCondition(array("A"),array("K","L","M"),-5)
);

/* 5. Если пользователь выбрал одновременно 3 продукта, он получает скидку 5% от суммы заказа
 * 6. Если пользователь выбрал одновременно 4 продукта, он получает скидку 10% от суммы заказа
 * 7. Если пользователь выбрал одновременно 5 продуктов, он получает скидку 20% от суммы заказа
 * 8. Описанные скидки 5,6,7 не суммируются, применяется только одна из них
 * 9. Продукты A и C не участвуют в скидках 5,6,7
 */
$countCondition = new CountCondition(
                          array(3,-5),
                          array(4,-10),
                          array(5,-20)
                        );
$countCondition->addExceptions(array("A","C"));
$cart->addCondition($countCondition);



echo " Товары в корзине: ".implode(",",$cart->getProducts())."<br>";
echo " Оригинальная сумма: ".$cart->getOriginalSum()."<br>";
echo " Сумма со скидками: ".$cart->getFinalSum()."<br>";
