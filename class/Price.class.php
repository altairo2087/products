<?php
/**
 * Class Price
 * Просто прайс всех товаров
 */
class Price {
    private static $products = array(
      "A" => 10,
      "B" => 20,
      "C" => 30,
      "D" => 40,
      "E" => 50,
      "F" => 60,
      "G" => 70,
      "H" => 80,
      "I" => 90,
      "J" => 100,
      "K" => 110,
      "L" => 120,
      "M" => 130,
    );

    /**
     *  Класс используется как статическая оболочка массива продуктов
     *  типа синглтона, но еще проще
     */
    private function __construct() {}
    private function __clone() {}

    /**
     * Проверка существования продукта
     * @param $name
     * @return bool
     * @throws Exception
     */
    public static function checkProduct($name) {
        if (!isset(self::$products[$name]))
            throw new Exception("Продукт \"".htmlspecialchars($name)."\" не существует");
        return TRUE;
    }

    /**
     * Возвращает цену продукта
     * @param $name
     * @return number
     */
    public static function getProductPrice($name) {
        if (self::checkProduct($name))
            return self::$products[$name];
    }
}