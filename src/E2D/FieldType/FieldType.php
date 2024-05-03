<?php

namespace E2D\FieldType;

use Core\App;
use Core\Field;
use Core\FilePath;
use Core\PathNode;

/**
 * Les classes "type de champ" permettent de personnaliser la génération des champs
 * en fonction des types
 * Class FieldType
 * @package E2D\FieldType
 */
abstract class FieldType
{
    private static $registeredTypes = [];
    protected $name;
    protected $mappingType = '';
    protected App $app;

    const BOOL = ['bool'];
    const DATE = ['date', 'datetime', 'time', 'timestamp'];
    const ENUM = ['enum', 'parametre'];
    const FLOAT = ['float', 'decimal', 'double'];
    const INTEGER = ['int', 'smallint', 'tinyint', 'bigint', 'primaryKey'];
    const STRING = ['string', 'varchar', 'char'];
    const TEXT = ['text', 'mediumtext', 'longtext'];
    const TIME = ['datetime', 'time'];

    /**
     * @param $key
     * @return FieldType
     */
    public static function create(string $name, App $app) : FieldType
    {
        if (array_contains($name, array_keys(self::$registeredTypes))) {
            return self::$registeredTypes[$name];
        }

        $key = self::getTypeKeyFromName($name);

        $match = [
            'primaryKey' => PrimaryKeyType::class,
            'bool'       => BoolType::class,
            'datetime'   => DateTimeType::class,
            'timestamp'  => TimestampType::class,
            'date'       => DateType::class,
            'enum'       => EnumType::class,
            'float'      => FloatType::class,
            'parametre'  => ParametreType::class,
            'foreignKey' => ForeignKeyType::class,
            'int'        => IntegerType::class,
            'string'     => StringType::class,
            'text'       => TextType::class,
            'time'       => TimeType::class,
        ];

        $type = new $match[$key]($name, $app);

        self::$registeredTypes[$name] = $type;

        return $type;
    }

    public function __construct($name, App $app)
    {
        $this->name = $name;
        $this->app = $app;
        $this->module = $this->app->getModuleName();
        $this->model = $this->app->getModelName();
    }

    private static function getTypeKeyFromName(string $name)
    {
        if (array_contains($name, ['bool', 'primaryKey', 'foreignKey', 'date', 'datetime', 'time', 'timestamp', 'parametre', 'enum'])) {
            $key = $name;
        }elseif (array_contains($name, self::FLOAT)) {
            $key = 'float';
        } elseif (array_contains($name, self::INTEGER)) {
            $key = 'int';
        } elseif (array_contains($name, self::TEXT)) {
            $key = 'text';
        } else {
            $key = 'string';
        }

        return $key;
    }

    /**
     * @param $type
     * @return bool
     */
    public function is($type)
    {
        if (is_array($type))
            return array_contains($this->type->name, $type);
        else
            return $this->name === $type;
    }

    public function __toString()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    public function getConsultationView($field, $template)
    {
        return str_replace(['LABEL', 'FIELD'], [$field->getLabel(), $field->getFormattedName()], $template);
    }

    public function getEditionView(FilePath $path)
    {
        return file_get_contents($this->app->getTrueTemplatePath($path->add('string')));
    }

    /**
     * @param string $indent
     * @param Field $field
     * @param array $template
     * @return string
     */
    public function addSelectFieldFormattedLines(string $indent, Field $field, array $template): string
    {
       return '';
    }

    /**
     * @param string $indent
     * @param Field $field
     * @param array $template
     * @return string
     */
    public function getCritereDeRecherche(string $indent, Field $field,  array $template) : string
    {
        $criteresRecherche = [];
        foreach ([['=', 9, ''], ['LIKE', 10, 'Partiel']] as [$operator, $templateIndex, $suffix]) {
            $texteCritere = $indent . implode('',array_map(function($line) use ($indent) {return $line.$indent;},
                    [$template[6], $template[0], $template[$templateIndex], $template[1], $template[2]]));

            $criteresRecherche[] = str_replace(['ALIAS', 'COLUMN', 'FIELD'],
                [$field->getAlias(), $field->getColumn(), $field->getName() . $suffix], $texteCritere);
        }

        return implode(PHP_EOL, $criteresRecherche);
    }

    public function getTemplateChampObligatoire(FilePath $templatePath)
    {
        $template = file($templatePath, FILE_IGNORE_NEW_LINES);
        return [$template[0], $template[1]];
    }

    public function getTemplateChampNullable(FilePath $templatePath)
    {
        $nullableFieldTemplatePath = $this->app->getTrueTemplatePath($templatePath->add('nullable'));

        return file_get_contents($nullableFieldTemplatePath);
    }

    function getValeurParDefautChampPourDynamisationEditionController(Field $field,  FilePath $fieldTemplatePath) : string
    {
        return '';
    }

    /**
     * Permet de récupérer le template du champ pour la vue recherche
     * @param PathNode $path
     * @return false|string
     */
    public function getVueRecherche(PathNode $path)
    {
        return file_get_contents($this->getCheminTemplateVueRecherche($path));
    }

    /**
     * Permet le récupérer le chemin du template du champ pour la vue recherche
     * @param $path
     * @return mixed
     */
    protected function getCheminTemplateVueRecherche($path)
    {
        return $this->app->getTrueTemplatePath($path->add('string'));
    }

    abstract public function getClasseMapping() : string;

    //////// LEGACY !!!!!!!!!!!!!!!!!!!!!!!

    /**
     * Référence template : templates/standard/module/controllers/CONTROLLERAdminAction_edition_champs_legacy.class.php
     * @param FilePath $templatePath
     * @return array
     */
    public function getTemplateChampObligatoireLegacy(FilePath $templatePath)
    {
        $template = file($templatePath, FILE_IGNORE_NEW_LINES);
        return [$template[0]];
    }

    /**
     * Référence template : templates/standard/module/controllers/CONTROLLERAdminAction_edition_champs_legacy.class.php
     * @param FilePath $templatePath
     * @return array
     */
    public function getTemplateChampNullableLegacy(FilePath $templatePath)
    {
        $template = file($templatePath, FILE_IGNORE_NEW_LINES);
        return $template[4];
    }


}