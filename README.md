# JustIn delivery service

Класс представляет собой оберутку для удобного использования API **JustIn** для языка **PHP**

##### Получение доступа
Для использования api вам необходимо получить ключи у предсавителей компании [JustIn](https://justin.ua/)

##### Использование
Передайте в конструктор класса свои доступы к api
```
$JustIn = new JustIn('login', 'pass', 'api_key');
```

Далее воспользуйтесь одним из методов для получения данных
- **getRegions** - Получить список областей
- **getAreasRegion** - Получить список обласных районов
- **getCities** - Получить список населенных пунктов
- **getCityRegions** - Получить список районов населенных пунктов
- **getCityStreets** - Получить список улиц
- **getDepartments** - Получить список отделений
- **createOrder** - Создать накладную (ТТН)
- **getStatusesList** - Получить список статусов
- **getOrderStatusesHistory** - Получить историю статусов закза
- **getOrderStickerLink** - Получить стикер заказа с именем
- **getOrderStickerLinkWithContact** - Получить стикер заказа с контактом

Для фильтрации результатов методы принимают массив фильтров
```
$JustIn->getCities([
  'name' => 'code | descr', filtered field name
  'comparison' => 'equal | not | less | more | in | between | not in | less or equal | more or equal | like',
  'leftvalue' => '', left limit
  'rightvalue' => '', right limit
]);
```
