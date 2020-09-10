<?php

namespace console\migrations\microservices\helper;

use Yii;
use Exception;

/**
 * Class WorkbenchFormatter
 *
 * Workbench export data
 * - from:
 *      id,level,title,alias,sort,isVisible,createdAt,updatedAt
 *      1,one,Товары,products,0,1,1598637570,1598637570
 * - to:
 *      INSERT IGNORE INTO categories (id, level, title, alias, sort, isVisible, createdAt, updatedAt)
 *      VALUES (1, 'one', 'Товары', 'products', 0, 1, 1598637570, 1598637570),
 *
 * @package console\migrations\microservices\helper
 */
class WorkbenchSQLFormatter
{
    private const FOLDER_PATH = '@console/migrations/microservices';

    /**
     * @var string
     */
    private string $from;

    /**
     * @var string
     */
    private string $to;

    /**
     * @var string
     */
    private string $table;

    /**
     * @var string
     */
    private string $folderPath;

    /**
     * @var string
     */
    private string $attributes;

    /**
     * @var array
     */
    private array $data;

    /**
     * @return static
     */
    public static function init(): self
    {
        return new static();
    }

    /**
     * @param string $from
     *
     * @return $this
     */
    public function setFrom(string $from): self
    {
        $this->from = $this->fileFormatter($from);

        return $this;
    }

    /**
     * @param string $to
     *
     * @return $this
     *
     */
    public function setTo(string $to): self
    {
        $this->to = $this->fileFormatter($to);

        return $this;
    }

    /**
     * @param string $table
     *
     * @return $this
     */
    public function setTable(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * @param string $folderPath
     *
     * @return $this
     */
    public function setFolderPath(string $folderPath): self
    {
        $this->folderPath = $folderPath;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function run()
    {
        if (!file_exists($this->getFrom())) {
            throw new Exception('Wrong path file `from`: ' . $this->getFrom());
        }

        file_put_contents($this->getTo(), $this->getContent());
    }

    private function parseContent()
    {
        $content = file_get_contents($this->getFrom());

        $toArray = explode("\n", $content);

        $this->setAttributes(array_shift($toArray));

        $this->setData($toArray);
    }

    /**
     * @return string
     */
    private function getContent(): string
    {
        $this->parseContent();

        return
            'INSERT IGNORE INTO'
            . " {$this->getTable()} {$this->getAttributes()}\n"
            . 'VALUES'
            . " {$this->getData()}";
    }

    /**
     * @return string
     */
    private function getTable(): string
    {
        return $this->table;
    }

    /**
     * @param string $attributes
     */
    private function setAttributes(string $attributes): void
    {
        $this->attributes = '(' . str_replace(',', ', ', $attributes) . ')';
    }

    /**
     * @return string
     */
    private function getAttributes(): string
    {
        return $this->attributes;
    }

    /**
     * @param array $data
     */
    private function setData(array $data): void
    {
        $result = [];

        foreach ($data as $row) {
            if (!is_string($row)) {
                continue;
            }

            if ($row === '') {
                continue;
            }

            $isDifficultString = strpos($row, '"') !== false;

            $prepareRow = [];

            if ($isDifficultString) {
                foreach (preg_split("/[\".*\"]+/", $row) as $ket => $value) {
                    $isSimpleString = substr($value, 0, 1) === ','
                        || substr($value, -1, 1) === ',';

                    if ($isSimpleString) {
                        foreach (explode(',', $value) as $item) {
                            if ($item === '') {
                                continue;
                            }

                            if (is_numeric($item)) {
                                $prepareRow[] = (int) $item;
                                continue;
                            }

                            if ($item === 'NULL') {
                                $prepareRow[] = 'null';
                                continue;
                            }

                            $prepareRow[] = "'$item'";
                        }
                        continue;
                    }

                    if ($value === '') {
                        continue;
                    }

                    $isArray = substr($value, 0, 1) === '['
                        && substr($value, -1, 1) === ']';

                    if ($isArray) {
                        $prepareRow[] = '\'' . $value . '\'';
                        continue;
                    }

                    $prepareRow[] = "'$value'";
                }
            } else {
                foreach (explode(',', $row) as $item) {
                    if ($item === '') {
                        continue;
                    }

                    if (is_numeric($item)) {
                        $prepareRow[] = (int) $item;
                        continue;
                    }

                    if ($item === 'NULL') {
                        $prepareRow[] = 'null';
                        continue;
                    }

                    $prepareRow[] = "'$item'";
                }
            }

            $result[] = '(' . implode(', ', $prepareRow) . ')';
        }

        $this->data = $result;
    }

    /**
     * @return string
     */
    private function getData(): string
    {
        return implode(", \n", $this->data) . ';';
    }

    /**
     * @return string
     */
    private function getPath(): string
    {
        if (isset($this->folderPath)) {
            return $this->folderPath;
        }

        return Yii::getAlias(
            self::FOLDER_PATH
        );
    }

    /**
     * @return string
     */
    private function getFrom(): string
    {
        return $this->getPath() . DIRECTORY_SEPARATOR . $this->from;
    }

    /**
     * @return string
     */
    private function getTo(): string
    {
        return $this->getPath() . DIRECTORY_SEPARATOR . $this->to;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function fileFormatter(string $name): string
    {
        if (substr($name, -4, 4) === '.sql') {
            return $name;
        }

        return $name . '.sql';
    }
}
