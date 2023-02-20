<?php

namespace Decadence;

use Illuminate\Database\Schema\Blueprint;

/**
 * Хелпер для миграций
 */
class Migrations
{

    /**
     * Метод для добавления столбца и внешнего ключа
     * @param Blueprint $table
     * @param string $foreignKey Столбец для внешнего ключа
     * @param string $onTable Таблица для внешнего ключа
     * @param string $referencesKey Столбец в родительской таблице
     * @param string $onUpdate Действие на onDelete
     * @param string $onDelete Действие на onDelete
     * @param bool $nullable Допустимы ли NULL
     * @param string $comment Комментарий к столбцу
     */
    public static function foreign(Blueprint $table,
                                   string    $foreignKey,
                                   string    $onTable,
                                   string    $referencesKey = "id",
                                   string    $onUpdate = "CASCADE",
                                   string    $onDelete = "CASCADE",
                                   bool      $nullable = true,
                                   string    $comment = ""
    )
    {
        $setNullOnDelete = $onDelete === "SET NULL";

        $column = $table->unsignedBigInteger($foreignKey);

        // создаём ключ, опционально с возможностью быть NULL
        if ($nullable || $setNullOnDelete) {
            $column->nullable();
        }

        if ($comment) {
            $column->comment($comment);
        }

        $table->foreign($foreignKey)
            ->on($onTable)
            ->references($referencesKey)
            ->onUpdate($onUpdate)
            ->onDelete($onDelete);
    }

}