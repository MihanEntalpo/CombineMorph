<?php

/**
 * Данный класс предназначен для создания узлов дерева, хранящегося в CombineMorph
 * и по сути он выполняет всю работу по конструированию дерева, созданию случайных текстов и т.д.
 *
 * @author mihanentalpo
 */
class CombineMorphElement
{
	//Скалярное значение, например простая строка "привет"
	const TYPE_SCALAR=0;
	//Последовательность - массив из разных типов нодов
	const TYPE_SEQUENCE=1;
	//Массив из разных элементов, из которых нужно выбрать один случайный
	const TYPE_OR=2;
	//Массив из разных элементов, которые нужно соединить в случайной последовательности
	const TYPE_AND=3;

	/**
	 * Тип данного элемента, по умолчанию - скаляр
	 * @var integer
	 */
	public $type=self::TYPE_SCALAR;

	/**
	 * Массив дочерних элементов, используется для всего кроме TYPE_SCALAR
	 * @var array
	 */
	public $items = array();

	/**
	 * Текст, хранящийся в скалярном элементе
	 * @var string
	 */
	public $scalarText = "";

	/**
	 * Функция, вычисляющая факториал числа
	 * @param integer $n
	 * @return integer
	 */
	protected function fact($n)
	{
		$f = 1;
		for($i=2;$i<=$n;$i++)
		{
			$f=$f * $i;
		}
		return $f;
	}

	/**
	 * Создать элемент. Обычно его создаёт CombineMorph. Вручную создавать его нет смысла
	 * @param string $src строка с текстом, который может содержать ссылки на "распарсенные элементы" в виде %#число#%, например %#1#%
	 * @param array $items массив строк, которые вырезаны из первоначальной строки и должны быть использованы при анализе текста $src как значения, на которые указывают &#1#&, &#2#& и т.д.
	 *
	 */
	public function __construct($src,$foundItems=array())
	{
		static $deep=0;
		$deep++;
		$spc = str_repeat("  ",$deep);
		//echo "{$spc}Parsing text...\n";
		if (substr($src,0,1)=='{' && substr($src,-1,1)=='}')
		{
			//echo "{$spc}It's an OR item: $src\n";
			$this->type = self::TYPE_OR;
			$src = substr($src,1,-1);
			$parts = explode("|",$src);
			foreach($parts as $part)
			{
				$this->items[] = new CombineMorphElement($part,$foundItems);
			}
		}
		else if(substr($src,0,1)=='[' && substr($src,-1,1)==']')
		{
			//echo "{$spc}It's an AND item: $src\n";
			$src = substr($src,1,-1);
			$this->type=self::TYPE_AND;
			$parts = explode("|",$src);
			foreach($parts as $part)
			{
				$this->items[] = new CombineMorphElement($part,$foundItems);
			}
		}
		else if(preg_match_all('|%#(?P<itemNum>[0-9]+)#%|',$src,$res,PREG_SET_ORDER))
		{
			//echo "{$spc}It's a sequence: $src\n";
			$this->type = self::TYPE_SEQUENCE;
			$this->items = array();

			$lastPos=0;
			foreach($res as &$itm)
			{
				$lastPos = strpos($src,$itm['0']);
				$itm['pos'] = $lastPos;
			}
			unset($itm);

			if ($res[0]['pos']>0)
			{
				$prepend = substr($src,0,$res[0]['pos']);
				//echo $prepend . "\n";
				$this->items[] = new CombineMorphElement($prepend,$foundItems);
			}

			foreach($res as $num=>$itm)
			{
				$this->items[] = new CombineMorphElement($foundItems[$itm['itemNum']],$foundItems);
				if ($num+1 < count($res))
				{
					$between = substr($src,$itm['pos']+strlen($itm[0]),$res[$num+1]['pos']-($itm['pos']+strlen($itm[0])));
					//echo "Between $num and ". ($num+1) . ": '" . $between . "'";
					$this->items[] = new CombineMorphElement($between, $foundItems);
					//echo " not last\n";
				}
			}

			$lastItm = end($res);
			if ($lastItm['pos'] + strlen($lastItm[0]) + 1 < strlen($src))
			{
				$after = substr($src,$lastItm['pos'] + strlen($lastItm[0]));
				$this->items[] = new CombineMorphElement($after, $foundItems);
				//echo "After: " . $after . "\n";
			}

		}
		else
		{
			//echo "{$spc}It's a scalar: '$src'\n";
			$this->scalarText = $src;
		}
		$deep--;
	}

	/**
	 * Возвращает случайны вариант текста текущего объекта, а также,  всех вложенных объектов
	 * @param boolean $addSpanOnRandom Добавлять <span class='random-combine-element'>вокруг любых элементов кроме скаляров</span> нужно для отладки
	 * @return string
	 */
	public function getRandomVariant($addSpanOnRandom=false)
	{
		if ($addSpanOnRandom)
		{
			$decor = function($text){return "<span class='random-combine-element'>" . $text . "</span>";};
		}
		else
		{
			$decor = function($text){return $text;};
		}

		$res="";
		switch ($this->type)
		{
			case self::TYPE_SCALAR:
				return $this->scalarText;
				break;
			case self::TYPE_SEQUENCE:
				foreach($this->items as $item)
				{
					$res .= $item->getRandomVariant($addSpanOnRandom);
				}
				return $res;
				break;
			case self::TYPE_AND:
				shuffle($this->items);
				foreach($this->items as $item)
				{
					$res .= " " . $item->getRandomVariant($addSpanOnRandom);
				}
				$res = trim($res);
				return $decor($res);
			break;
			case self::TYPE_OR:
				$key = array_rand($this->items);
				$res = $this->items[$key]->getRandomVariant($addSpanOnRandom);
				return $decor($res);
			break;
		}
	}

	/**
	 * Подсчитывает количество вариантов текста, которые могут быть сгенерированы данным элементом
	 * @return int
	 */
	public function getVariantsCount()
	{
		switch ($this->type)
		{
			case self::TYPE_SCALAR:
				return 1;
				break;
			case self::TYPE_SEQUENCE:
				$res = 1;
				foreach($this->items as $item)
				{
					$res = $res * $item->getVariantsCount();
				}
				if ($res==0) echo "sequence res=0\n";
				return $res;
				break;
			case self::TYPE_AND:
				$res = $this->fact(count($this->items));
				foreach($this->items as $item)
				{
					$res = $res * $item->getVariantsCount();
				}
				if ($res==0) echo "AND res=0\n";
				return $res;
			break;
			case self::TYPE_OR:
				$res = 0;
				foreach($this->items as $item)
				{
					$res = $res + $item->getVariantsCount();
				}
				if ($res==0) echo "OR res=0\n";
				return $res;
			break;
		}
	}

}


?>
