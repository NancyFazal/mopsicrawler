<?php

/**
 * Created by PhpStorm.
 * User: khuen
 * Date: 05-Jul-17
 * Time: 22:52
 */
class Install extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->dbforge();
        $this->load->database();
        $this->load->helper('file');
        $this->load->helper('url');
    }

    public function execute()
    {
        echo "Creating tables...\n";
        $this->create_tables();
    }

    public function clear()
    {
        $this->dbforge->drop_table('url_queue', true);
        $this->dbforge->drop_table('seed_pages', true);
        $this->dbforge->drop_table('pages', true);
        $this->dbforge->drop_table('page_texts', true);
        $this->dbforge->drop_table('images', true);
        echo "Cleared all installed tables!\n";
    }

    protected function create_tables()
    {
        $this->tbl_url_queue();
        echo "Created url_queue table\n";
        $this->tbl_seed_pages();
        echo "Created seed_pages table\n";
        $this->tbl_pages();
        echo "Created pages table\n";
        $this->tbl_images();
        echo "Created images table\n";
    }

    protected function tbl_url_queue()
    {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
                'unsigned' => true
            ),
            'parent_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true
            ),
            'seed_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true
            ),
            'url' => array(
                'type' => 'VARCHAR',
                'constraint' => 512,
            ),
            'url_desc' => array(
                'type' => 'VARCHAR',
                'constraint' => 512,
            ),
            'host' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'unsigned' => true
            ),
            'relevance' => array(
                'type' => 'DOUBLE'
            ),
        );
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('url_queue', true, array('ENGINE' => 'InnoDB'));
    }

    protected function tbl_seed_pages()
    {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
                'unsigned' => true
            ),
            'url' => array(
                'type' => 'VARCHAR',
                'constraint' => '512'
            ),
            'url_desc' => array(
                'type' => 'VARCHAR',
                'constraint' => '512'
            ),
            'host' => array(
                'type' => 'VARCHAR',
                'constraint' => 255
            ),
            'finished' => array(
                'type' => 'SMALLINT',
                'constraint' => 1,
                'default' => 0
            )
        );

        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('seed_pages', true, array('ENGINE' => 'InnoDB'));

        // Populate seed page with data
        $data_file_path = base_url('data/seed_pages.csv');
        $content = read_file($data_file_path);
        $lines = explode("\n", $content);
        $data = array();
        foreach ($lines as $line) {
            $data[] = str_getcsv($line);
        }

        foreach ($data as $seed) {
            $this->db->insert('seed_pages', array(
                'url' => $seed[0],
                'url_desc' => $seed[1],
                'host' => parse_url($seed[0])['host'],
                'finished' => $seed[2]
            ));
        }
    }

    protected function tbl_pages()
    {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
                'unsigned' => true
            ),
            'parent_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'default' => 0
            ),
            'seed_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'default' => 0
            ),
            'url' => array(
                'type' => 'VARCHAR',
                'constraint' => 512,
            ),
            'url_desc' => array(
                'type' => 'VARCHAR',
                'constraint' => 512,
            ),
            'host' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'unsigned' => true
            ),
            'title' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'default' => '',
                'null' => true
            ),
            'text' => array(
                'type' => 'TEXT',
                'default' => '',
                'null' => true
            ),
            'keywords' => array(
                'type' => 'VARCHAR',
                'constraint' => 512,
                'default' => '',
                'null' => true,
            ),
            'relevance' => array(
                'type' => 'DOUBLE',
                'null' => true,
            )
        );
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('pages', true, array('ENGINE' => 'InnoDB'));
    }

    protected function tbl_images()
    {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
                'unsigned' => true
            ),
            'filename' => array(
                'type' => 'VARCHAR',
                'constraint' => 255
            ),
            'source' => array(
                'type' => 'VARCHAR',
                'constraint' => 512
            ),
            'hash' => array(
                'type' => 'VARCHAR',
                'constraint' => 255
            ),
            'src' => array(
                'type' => 'VARCHAR',
                'constraint' => 512
            ),
            'path' => array(
                'type' => 'VARCHAR',
                'constraint' => 512
            ),
            'width' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true
            ),
            'height' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true
            ),
            'type' => array(
                'type' => 'VARCHAR',
                'constraint' => 5
            ),
            'alt' => array(
                'type' => 'VARCHAR',
                'constraint' => 512,
                'null' => true,
                'default' => ''
            ),
            'author' => array(
                'type' => 'VARCHAR',
                'constraint' => 128,
                'null' => true,
                'default' => ''
            ),
            'copyright' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => ''
            ),
            'related_texts' => array(
                'type' => 'TEXT',
                'null' => true,
                'default' => ''
            ),
            'latitude' => array(
                'type' => 'DECIMAL(10,6)',
                'null' => true,
            ),
            'longitude' => array(
                'type' => 'DECIMAL(10,6)',
                'null' => true,
            ),
            'location_name' => array(
                'type' => 'VARCHAR',
                'constraint' => 128,
                'null' => true,
                'default' => ''
            ),
            'is_exif_location' => array(
                'type' => 'SMALLINT',
                'constraint' => 1,
                'null' => true,
                'default' => 0
            ),
            'date_taken' => array(
                'type' => 'DATE',
                'null' => true
            ),
            'date_acquired' => array(
                'type' => 'DATE',
                'null' => true
            ),
        );
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('images', true, array('ENGINE' => 'InnoDB'));
    }
}