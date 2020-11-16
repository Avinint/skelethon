<?php


namespace E2D;


use Core\Action;
use Core\App;
use Core\FilePath;


class ExportAction extends Action
{
    protected string $name = 'export';

    /**
     * @param FilePath $path
     * @return string
     */
    public function generateRoutingFile(FilePath $path) : string
    {
        if ($path->getName() === 'blocs') {
            return '';
        }

        if ($this->app->get('avecRessourceExport') ?? false) {
            return '';
        }

        return parent::generateRoutingFile($path);
    }

    public function __construct(App $app)
    {
        parent::__construct($app);

        if (!$this->app->has('avecRessourceExport') ) {
            $this->model->askAvecRessourceExport();
        }

        if ($this->app->get('avecRessourceExport') ?? false) {
           if (!($this->model->ressourceExportInstallee() && $this->model->tablesExportCreees())) {
               $this->msg('fonctionnalité export indisponible : table et/ou ressource export manquante(s)', 'important');
               $this->model->removeAction('export');
           }

        } else {
            $this->msg("Ukulele", 'error');
        }

    }

}