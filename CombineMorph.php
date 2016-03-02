<?php

require_once __DIR__ . "/CombineMorphElement.php";

/**
 * Класс CombineMorph предназначен для генерации текстов на основе комбинаций разных их элементов.
 * Синтаксис текстовых данных, которые умеет обрабатывать данный класс:
 * {Вариант1|Вариант2|Вариант3} - такая конструкция заменяется на один из вариантов, разделенных "|"
 * [Вариант1|Вариант2|Вариант3] - такая конструкция заменяется на перечисление всех её вариантов, в случайном порядке
 * конструкции могут быть в произвольной последовательности и любой глубины вложенности, например такой вот рассказик:
 * <pre>
 * Итак: {Однажды|В один из дней|Как-то раз}, в {{студёную|холодную} зимнюю |{жаркую|душную|знойную} летнюю}
 * {пору|погоду}, {я|он} { из {лесу|кустов|чащи} {вышел|выполз|вылетел}| выскочил из {танка|джипа|запорожца}}
 * - {был {сильный|лютый|страшный} {мороз|холод|дубак}|была {лютая|адская|сумасшедшая} {жара|духота}}.
 * </pre>
 *
 * @author mihanentalpo
 */
class CombineMorph
{
	/**
	 * @var CombineMorphElement Корневой элемент
	 */
	public $root=null;
	/**
	 * Создать новый комбинатор
	 * @param string $src Исходная строка с текстом, который нужно подвергнуть трансформации
	 */
	public function __construct($src)
	{
		$items = array();

		$start = 0;
		$length = 0;
		$type=0;
		while($part = $this->findDeepestANDOR($src,$start,$length,$type))
		{
			$items[] = $part;
			//echo " Part#" . (count($items)-1) .  ": " . $part  . ", TYPE:" . $type . "\n";
			$src = substr($src,0,$start) . "%#" . (count($items)-1) . "#%" . substr($src,$start+$length);
			//echo "Other after cut: " . $src . "\n";
		}

		$this->root = new CombineMorphElement($src,$items);

	}

	/**
	 * Функция, находящая самую вложенную конструкцию {a|b|c} или [a|b|c]
	 * используется для внутренних нужд
	 * @param string $txt исходный текст
	 * @param integer $start сюда запишется начальный символ
	 * @param integer $length сюда запишется длина найденой строки
	 * @param integer $type сюда запишется тип (TYPE_OR или TYPE_AND)
	 * @return boolean|string если ничего не найдено - вернет False, если найдено - вернет найденную строку
	 */
	protected function findDeepestANDOR($txt,&$start,&$length,&$type)
	{
		if (preg_match("#\{[^\[\]\}\{]+\}#im",$txt,$res))
		{
			$part = $res[0];
			$length = strlen($part);
			$start = strpos($txt,$part);
			$type = CombineMorphElement::TYPE_OR;

			return $part;

		}
		elseif (preg_match("#\[[^\[\]\}\{]+\]#im",$txt,$res))
		{
			$part = $res[0];
			$length = strlen($part);
			$start = strpos($txt,$part);
			$type = CombineMorphElement::TYPE_AND;

			return $part;

		}
		else
		{
			return false;
		}
	}

	/**
	 * Генерирует случайный вариант текста
	 * @param boolean $addSpanOnRandom Добавлять <span class='random-combine-element'>вокруг любых элементов кроме скаляров</span> нужно для отладки
	 * @return string
	 */
	function getRandomVariant($addSpanOnRandom=false)
	{
		return $this->root->getRandomVariant($addSpanOnRandom);
	}

	/**
	 * Подсчитывает количество вариантов текста, которые можно сгенерировать
	 * @return integer
	 */
	function getVariantsCount()
	{
		return $this->root->getVariantsCount();
	}

}

?>
