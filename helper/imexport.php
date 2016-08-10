<?php
/**
 * DokuWiki Plugin struct (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr, Michael Große <dokuwiki@cosmocode.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class helper_plugin_struct_imexport extends DokuWiki_Plugin {

    private $sqlite;


    /**
     * this possibly duplicates @see helper_plugin_struct::getSchema()
     */
    public function getAllSchemasList() {
        /** @var \helper_plugin_struct_db $helper */
        $helper = plugin_load('helper', 'struct_db');
        $this->sqlite = $helper->getDB();

        $sql = 'SELECT DISTINCT(tbl) FROM schemas';
        $res = $this->sqlite->query($sql);
        $schemas = $this->sqlite->res2arr($res);
        $this->sqlite->res_close($res);
        return $schemas;
    }

    /**
     * @param string   $schema
     * @param string[] $assignments
     */
    public function replaceSchemaAssignmentPatterns($schema, $patterns) {
        /** @var \helper_plugin_struct_db $helper */
        $helper = plugin_load('helper', 'struct_db', true);
        $this->sqlite = $helper->getDB();
        $schema = $this->sqlite->escape_string($schema);
        $sql = array();
        $sql[] = "DELETE FROM schema_assignments_patterns WHERE tbl = '$schema'";
        $sql[] = "DELETE FROM schema_assignments WHERE tbl = '$schema'";
        foreach ($patterns as $pattern) {
            $pattern = $this->sqlite->escape_string($pattern);
            $sql[] = "INSERT INTO schema_assignments_patterns (pattern, tbl) VALUES ('$pattern','$schema')";
        }

        $this->sqlite->doTransaction($sql);
        $assignments = new \dokuwiki\plugin\struct\meta\Assignments();
        $assignments->propagatePageAssignments($schema);
    }

    public function getSchemaAssignmentPatterns($schema) {
        /** @var \helper_plugin_struct_db $helper */
        $helper = plugin_load('helper', 'struct_db', true);
        $this->sqlite = $helper->getDB();

        $sql = 'SELECT pattern FROM schema_assignments_patterns WHERE tbl = ?';
        $res = $this->sqlite->query($sql, $schema);
        $patterns = $this->sqlite->res2arr($res);
        $this->sqlite->res_close($res);
        return array_map(function($elem){return $elem['pattern'];},$patterns);
    }

    public function getCurrentSchemaJSON($schema) {
        $schema = new \dokuwiki\plugin\struct\meta\Schema($schema);
        return $schema->toJSON();
    }

    public function importSchema($schemaName, $json) {
        $importer = new \dokuwiki\plugin\struct\meta\SchemaImporter($schemaName, $json); // todo could throw a struct exception?!
        $ok = $importer->build(); // ToDo: Ensure that user = FARMSYNC is set
        return $ok;
    }

}
