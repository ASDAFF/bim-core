<?php

/**
 * Main class of creating migration is required for the generation of migration files.
 *
 * The following commands create:
 *   - iblock
 *   - other
 *
 */
class GenCommand extends BaseCommand {

    # generate object
    private $gen_obj = null;

    /**
     * execute
     * @param array $args
     * @param array $options
     * @return mixed|void
     * @throws Exception
     */
    public function execute(array $args, array $options = array())
    {
        if (isset($args[0])) {
            #chemethod
            if (strstr($args[0], ':')) {
                $ex = explode(":",$args[0]);
                $this->setGenObj(Bim\Db\Lib\CodeGenerator::buildHandler(ucfirst($ex[0])));
                $methodName = ucfirst($ex[0]).ucfirst($ex[1]);
            } else {
                throw new Exception("Improperly formatted command. Example: php bim create iblock:add");
            }
            $method = "gen" . $methodName;
            if (method_exists($this,$method)) {
                $this->{$method}($args, $options);
            } else {
                throw new Exception("Missing command, see help Example: php bim help create");
            }
        } else {
            $this->createOther($args,$options);
        }
    }

    /**
     * createIblock
     * @param array $args
     * @param array $options
     */
    public function genIblockAdd(array $args, array $options = array())
    {
        $dialog  = new \ConsoleKit\Widgets\Dialog($this->console);

        # get description options
        $desc = (isset($options['d'])) ? $options['d'] : "";
        if (!is_string($desc)) {
            $desk = "Type Description of migration file. Example: #TASK-124";
            $desc = $dialog->ask($desk.PHP_EOL.$this->color('Description:',\ConsoleKit\Colors::BLUE), "",false);
        }

        $do = true;
        while ($do) {
            $desk = "Information block type - no default/required";
            $field_val = $dialog->ask($desk . PHP_EOL . $this->color('[IBLOCK_ID]:', \ConsoleKit\Colors::YELLOW), '', false);
            $up_data['iblockId'] = $this->clear($field_val);

            $iblockTypeDbRes = \CIBlockType::GetByID($up_data['iblockId']);
            if ($iblockTypeDbRes->SelectedRowsCount()) {
                $do = false;
            }
        }

        # set
        $name_migration = $this->getMigrationName();
        $this->saveTemplate($name_migration,
            $this->setTemplate(
                $name_migration,
                $this->gen_obj->generateAddCode($up_data),
                $this->gen_obj->generateDeleteCode($up_data),
                $desc,
                get_current_user()
            ));
    }

    /**
     * createIblockDelete
     * @param array $args
     * @param array $options
     */
    public function createIblockDelete(array $args, array $options = array())
    {
        # Up Wizard
        $up_data = array();
        $down_data = array();
        $desc = "";

        # create wizard command
        $wizard = new \Bim\Db\Iblock\IblockCommand($this->getConsole());
        $wizard->deleteWizard($up_data,$down_data,$desc);

        # set
        $temp =  "up";
        $name_migration = $this->getMigrationName();
        $this->saveTemplate($name_migration,
            $this->setTemplate(
                $name_migration,
                $this->setTemplateMethod('iblock', 'delete', $up_data, $temp ),
                $this->setTemplateMethod('iblock', 'delete', $down_data, "down"),
                $desc,
                get_current_user()
            ));
    }


    /**
     * createOther
     * @param array $args
     * @param array $options
     */
    public function createOther(array $args, array $options = array())
    {
        # get description options
        $desc = (isset($options['d'])) ? $options['d'] : "";
        if (!is_string($desc)) {
            $dialog = new \ConsoleKit\Widgets\Dialog($this->console);
            $desc = $dialog->ask('Description:', '', false);
        }

        $up_data = array();
        $down_data = array();

        $name_method = "other";
        # set
        $name_migration = $this->getMigrationName();
        $this->saveTemplate($name_migration,
            $this->setTemplate(
                $name_migration,
                $this->setTemplateMethod(strtolower($name_method), 'create', $up_data),
                $this->setTemplateMethod(strtolower($name_method), 'create', $down_data, "down"),
                $desc,
                get_current_user()
            ));
    }

    /**
     * @return null
     */
    public function getGenObj()
    {
        return $this->gen_obj;
    }

    /**
     * @param null $gen_obj
     */
    public function setGenObj($gen_obj)
    {
        $this->gen_obj = $gen_obj;
    }

}