                case 'dynamisation_edition':
                    $aRetour = $this->aDynamisationEdition($IDFIELD);
                    break;

                case 'creation':
                case 'modification':
                    $aRetour = $this->aEnregistreEdition($IDFIELD);
                    break;
