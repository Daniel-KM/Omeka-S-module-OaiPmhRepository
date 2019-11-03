# Table: Stores currently active resumptionTokens
#
# id: primary key (also the value of the token)
# verb: Verb of original request
# metadata_prefix: metadataPrefix of original request
# cursor: Position of cursor within result set
# from: Optional from argument of original request
# until: Optional until argument of original request
# set: Optional set argument of original request
# expiration: Datestamp after which token is expired

CREATE TABLE oai_pmh_repository_token (
    `id` INT AUTO_INCREMENT NOT NULL,
    `verb` VARCHAR(190) NOT NULL,
    `metadata_prefix` VARCHAR(190) NOT NULL,
    `cursor` INT NOT NULL,
    `from` DATETIME DEFAULT NULL,
    `until` DATETIME DEFAULT NULL,
    `set` VARCHAR(190) DEFAULT NULL,
    `expiration` DATETIME NOT NULL,
    INDEX IDX_E9AC4F9524CD504D (`expiration`),
    PRIMARY KEY(`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
