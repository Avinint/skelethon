                LEFT JOIN FKTABLE FKALIAS
                ON ALIAS.FK = FKALIAS.FK
                ON FKALIAS.code = ALIAS.COLUMN AND FKALIAS.type = \'TYPE\'