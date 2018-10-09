<?php
/**
 * Created by PhpStorm.
 *
 * @author: Bad Wolf
 * @date: 12.09.2018 11:07
 */


//require_once "C:\OpenServer/domains/moodle/config.php";
require_once "W:/domains/moodle/config.php";

//require_once "C:\OpenServer/domains/moodle/blocks/moodleblock.class.php";
global $CFG;
require_once $CFG->dirroot . "/blocks/moodleblock.class.php";
require_once $CFG->libdir . "/custom/autoload.php";

class block_programs extends block_base
{

    public function init()
    {
        $User = Core::factory( "User" )->getCurrent();

        if( $User->getRoleId() === 1 )      $this->title = "Программы";
        elseif( $User->getRoleId() === 5 )  $this->title = "Список заявок для записи на программу";
    }


    public function get_content()
    {
        $User = Core::factory( "User" )->getCurrent();

        $this->content = new stdClass;

        if( $User->getRoleId() === 1 )
        {
            $this->content->text = "Список программ";

            $Programs = Core::factory( "Program" )->findAll();
            $Types = Core::factory( "Program_Type" )->findAll();
            $Forms = Core::factory( "Program_Form" )->findAll();

            $this->content->text = Core::factory( "Core_Entity" )
                ->addEntities( $Programs )
                ->addEntities( $Types )
                ->addEntities( $Forms )
                ->xsl( "tables/admin/programs.xsl" )
                ->show( false );
        }
        elseif( $User->getRoleId() === 5 )
        {
            $Applications = Core::factory( "Program_Application" )
                ->queryBuilder()
                ->select( ["mdl_program_application.id", "user_id", "period_id", "pr.id AS program_id", "pr.title", "date_start", "date_end"] )
                ->where( "user_id", "=", $User->getId() )
                ->join( "mdl_program_period AS per", "per.id = period_id" )
                ->join( "mdl_program AS pr", "pr.id = per.program_id" )
                ->findAll();

            foreach ( $Applications as $App )
            {
                $App->date_start = date( "m.d.Y", strtotime( $App->date_start ) );
                $App->date_end = date( "m.d.Y", strtotime( $App->date_end ) );

                $files = Core::factory( "File" )
                    ->queryBuilder()
                    ->where( "user_id", "=", $User->getId() )
                    ->where( "period_id", "=", $App->getPeriodId() )
                    ->where( "file_type_id", "IN", [3, 4, 5, 7] )
                    ->findAll();

                $App->addEntities( $files );
            }

            $this->content->text = Core::factory( "Core_Entity" )
                ->addEntities( $Applications, "app" )
                ->xsl( "tables/applications.xsl" )
                ->show( false );
        }

        return $this->content;
    }



    /**
     * Locations where block can be displayed.
     *
     * @return array
     */
    public function applicable_formats() {
        return array('my' => true);
    }

}