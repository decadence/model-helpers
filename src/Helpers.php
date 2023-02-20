<?php

namespace Decadence;

trait Helpers
{
    /**
     * Ключ кеширования для модели с учётом времени её
     * обновления
     * @param string $postfix
     * @return string
     */
    public function cacheKey($postfix)
    {
        $modelKey = $this->getKey();

        if ($this->usesTimestamps()) {
            $modelKey .= "." . $this->getAttribute("updated_at");
        }

        return static::class . ".{$modelKey}.{$postfix}";
    }

    /**
     * Синхронизация отношения hasMany
     * @param string $name Имя отношения
     * @param array $data Массив данных в определенном формате
     * @param bool $delete Удалять ли записи, данные для которых не переданы
     * @return bool Был ли изменен состав отношения
     *
     */
    public function syncMany($name, $data = [], $delete = true)
    {
        // изменился ли состав записей
        $changed = false;

        // текущее значение отношения
        $relation = $this->getRelationValue($name);

        // на случай пустых данных
        $data = $data ?: [];

        // переданные id записей отношения
        $posted = Arr::pluck($data, "id");

        // если включено удаление непереданных отношений
        if ($delete) {

            // id существующих записей отношения
            $existingIds = $relation->pluck("id")->toArray();

            // находим не переданные id
            // разница между существующими и присланными
            $diff = array_diff($existingIds, $posted);

            // и удаляем их
            foreach ($diff as $toDelete) {
                $changed = true;

                $this->$name()
                    ->findOrNew($toDelete)
                    ->delete();
            }
        }

        // проходим по всем переданным строкам
        foreach ($data as $key => $relationData) {

            // можно использовать data_get, но зато сразу видно
            // ошибку непереданного id
            $relatedId = $relationData["id"];

            // находим запись из отношения или создаём новую
            /** @var Model $model */
            $model = $this->$name()->findOrNew();

            // или поиск через коллекцию, если модели нужны измененные
            // $model = $relation->find($relationData["id"], $this->$name()->getRelated());

            $model->fill($relationData);

            // если модель изменена (новая будет изменена в любом случае)
            if ($model->isDirty()) {
                $changed = true;
            }

            $this->$name()->save($model);
        }

        return $changed;
    }

}