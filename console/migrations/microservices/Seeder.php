<?php

namespace console\migrations\microservices;

use console\helpers\Color;
use Yii;

/**
 * Class Seeder
 *
 * @package console\migrations\microservices
 */
class Seeder
{
    private const LOCAL_FOLDER_PATH = '@console/migrations/microservices';

    /**
     * @return array
     * @example SQL file must has identical name ('countries.sql') as the $model::tableName() ('countries')
     *
     */
    public static function getTables(): array
    {
        return [
//            SomeTable::tableName(),
        ];
    }

    public static function run(): void
    {
        $db          = Yii::$app->db;
        $transaction = $db->beginTransaction();
        echo Color::LIGHT_PURPLE('seeder run:');
        $projectAlias = Yii::$app->params['projectAlias'] ?? 'panel';

        try {
            foreach (self::getTables() as $table) {

                $path = Yii::getAlias(
                    self::LOCAL_FOLDER_PATH
                    . DIRECTORY_SEPARATOR
                    . $projectAlias
                    . DIRECTORY_SEPARATOR
                    . $table . '.sql'
                );

                if (!file_exists($path)) {
                    continue;
                }

                $sql = file_get_contents($path);

                $db->createCommand($sql)->execute();

                echo Color::GREEN(" - {$table} seeding success");
            }

            $transaction->commit();
            echo Color::LIGHT_PURPLE('seeder finished');

        } catch (\Exception $exception) {
            $transaction->rollBack();

            Yii::error($exception->getMessage(), 'seeding');

            echo Color::RED('Exception with message:');
            echo "{$exception->getMessage()}\n";
            echo Color::RED('microservice stop!');
            exit();
        }
    }
}
