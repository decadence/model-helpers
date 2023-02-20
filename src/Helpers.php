<?php

namespace Decadence;

use InvalidArgumentException;

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
    public function syncMany(string $name, array $data = [], bool $delete = true)
    {
        // изменился ли состав записей
        $changed = false;

        // текущее значение отношения
        $relation = $this->getRelationValue($name);

        // переданные id записей отношения
        $posted = Arr::pluck($data, "id");

        // если включено удаление непереданных отношений
        if ($delete) {

            // id существующих записей отношения
            $existingIds = $relation->pluck("id")->toArray();

            // находим непереданные id
            // разница между существующими и присланными
            $diff = array_diff($existingIds, $posted);

            // и удаляем их
            foreach ($diff as $toDelete) {
                $changed = true;

                $modelToDelete = $this->$name()
                    ->find($toDelete);

                // если в отношении есть модель с таким id
                // удаляем её
                if($modelToDelete) {
                    $modelToDelete->delete();
                }
            }
        }

        // проходим по всем переданным строкам
        foreach ($data as $key => $relationData) {

            // проверяем именно наличия ключа, потому что он может
            // быть и null
            if(array_key_exists("id", $relationData)) {
                throw new InvalidArgumentException("Не найден id для syncMany");
            }

            $relatedId = $relationData["id"];

            // находим запись из отношения или создаём новую
            /** @var Model $model */
            $model = $this->$name()->findOrNew($relatedId);

            // или поиск через Collection, если модели нужны измененные
            // $model = $relation->find($relatedId, $this->$name()->getRelated());

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