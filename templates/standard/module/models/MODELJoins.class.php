               // inutilisé
                , (
                    SELECT CONCAT(FKALIAS.label)
                    FROM FKTABLE FKALIAS
                    WHERE FKALIAS.COLUMN = ALIAS.COLUMN
                ) AS FIELD