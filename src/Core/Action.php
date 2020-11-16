<?php


namespace Core;


abstract class Action extends BaseMaker
{
    protected string $name;
    protected App $app;
    protected ModelMaker $model;

    public function __toString()
    {
        return $this->name;
    }

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->model = $app->getModelMaker();
    }

    /**
     * @param FilePath $path
     * @return string
     */
    public function generateRoutingFile(FilePath $path) : string
    {
        if (strpos($path, 'blocs') && !array_contains($this->name, ['consultation', 'edition'])) {
            return '';
        }



        $templatePerActionPath = $this->getTrueTemplatePath($path->add($this->name));
        if (isset($templatePerActionPath)) {
            return $this->getConfigTemplateForAction($templatePerActionPath, $path, $this->name);
        }

        return '';
    }

    /**
     * Récupère le template pour générer un fichier action, routing ou bloc par action
     * @param string $templatePerActionPath
     * @param string $path
     * @param string $action
     * @return string
     */
    private function getConfigTemplateForAction(FilePath $templatePerActionPath, FilePath $path, string $action): string
    {
        return file_get_contents($templatePerActionPath) .
            $this->makeMultiModalBlock($path, $templatePerActionPath);
    }

    /**
     * Ajoute les lignes permettant les calques multiples, dans les blocs
     * @param PathNode $path
     * @param string $action
     * @param PathNode $templatePerActionPath
     * @return false|string
     */
    public function makeMultiModalBlock(FilePath $path, FilePath $templatePerActionPath)
    {
        /**
         * TODO remplacer par Path
         */
        return ($this->app->get('usesMultiCalques') && strpos($path, 'blocs') !== false ?
            file_get_contents(str_replace($this->name, 'multi', $templatePerActionPath)) : '');
    }

    /**
     * @param FilePath $path
     * @param bool $usesRechercheNoCallback
     * @param string $actionMethodText
     * @return string
     */
    public function getJavaScriptMethods(FilePath $path, bool $usesCallbackListeElement) : string
    {
        $templatePerActionPath = $this->getTrueTemplatePath($path->add($this->camelize($this->name)));

        if (isset($templatePerActionPath)) {
            $templatePerActionPath = $this->getJavaScriptMethodPerActionHook($usesCallbackListeElement, $templatePerActionPath);

            return file_get_contents($templatePerActionPath);
        }

        return '';
    }

    /**
     * @param bool $usesRechercheNoCallback
     * @param FilePath $templatePerActionPath
     * @return FilePath
     */
    protected function getJavaScriptMethodPerActionHook(bool $usesRechercheNoCallback, FilePath $path) : FilePath
    {
        return $path;
    }

    public function getNoRechercheText(string $path)
    {
        return   file_get_contents($this->getTrueTemplatePath($path->add('noRecherche')));
    }
}