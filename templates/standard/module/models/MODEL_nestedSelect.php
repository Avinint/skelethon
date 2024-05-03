                (
                    SELECT LABEL
                    FROM FKTABLE
                    WHERE PK = ALIAS.PK
                ) AS FIELD');
