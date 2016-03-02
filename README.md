# CombineMorph
Генератор / множитель текстов

## Использование

**Подключение:**

```php
require_once "CombineMorph.php";
```

**Инициализация:**

```php
$combineMorph = new CombineMorph("Текс с {разными|различными} вариантами в {виде|формате|форме} специального синтаксиса");
```

**Получение количества возможных вариантов данного текста:**

```php
$variantsCount = $combineMorph->getVariantsCount();
```

**Генерация нескольких случайных вариантов текста:**

```php
for($i=0;$<10;$i++)
{
    $variant[$i] = $combineMorph->getRandomVariant();
}
```
