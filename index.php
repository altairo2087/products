<?php
header("Content-type: text/html;charset=utf-8");

include "class/Price.class.php";
include "class/Cart.class.php";
include "class/Condition.class.php";

// добавляем продукты
$cart = new Cart();
$cart->addProduct(array("A","A","B","D","E","E","F","G"));
$cart->addProduct(array("K","L"));
$cart->addProduct("M");
$cart->addProduct(array("B","A","C","B","B"));



// добавляем условия рассчета цен
$cart->addCondition(
  new ConjCondition(array("A","B"), -10)
);
$cart->addCondition(
  new ConjCondition(array("D","E"), -5)
);
$cart->addCondition(
  new ConjCondition(array("E","F","G"), -5)
);
$cart->addCondition(
  new DisjCondition(array("A"),array("K","L","M"),-5)
);

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
