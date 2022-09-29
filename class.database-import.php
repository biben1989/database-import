<?php


class DataBaseImport{



    private   $seconddb;
    public $table = '';
    private string $HOST = "localhost";
    private string $USER = "root";
    private string $PASS = "";
    private string $DATABASE = "crimea_old_db";

    public function __construct()
    {
        global $seconddb;

        $this->seconddb = new wpdb($this->USER, $this->PASS, $this->DATABASE, $this->HOST);

        add_action( 'admin_menu', array($this,'database_import_menu'), 25 );
        add_action( 'admin_action_import', array($this,'import_admin_action') );
    }

    public function import_admin_action()
    {
        $this->setTable($_POST['table_name']);
        $this->insertPost();

        exit();

    }

    public function getDb(){
        return $this->seconddb;
    }

    public function setTable(string $table): void
    {
        $this->table = $table;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function database_import_menu(){

        add_menu_page(
            'Импорт Базы Данных',
            'Импорт Базы Данных',
            'manage_options',
            'database_import',
            array($this,'database_import_page_callback'),
            'dashicons-database-import',
            20
        );
    }

    public function database_import_page_callback(){?>
        <div class="content">
            <form method="post" action="<?php echo admin_url( 'admin.php' ); ?>">
                <input type="hidden" name="action" value="import" />

                <h2>Import Data Base</h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                Post Type
                            </th>
                            <td>
                                <select name="post_type">
                                    <option value="post">Post</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                Data Table
                            </th>
                            <td>
                                <select name="table_name">
                                    <option value="publications">Publications</option>
                                </select>
                            </td>
                        </tr>

                    </tbody>
                </table>
                    <p class="submit">
                        <input type="submit" name="submit" id="submit" class="button button-primary" value="Import">
                    </p>
            </form>
        </div>
    <?php }

    static public function activation()
    {
        flush_rewrite_rules();
    }

    static public function deactivation()
    {
        flush_rewrite_rules();
    }

    public function dbSelect()
    {
        return $this->getDb()->get_results("SELECT * FROM `$this->table`");
    }


    private function insertPost()
    {

        foreach ($this->dbSelect() as $publication) {

            $active = [
                0 =>'draft',
                1 =>'publish'
            ];

            $data = [
                    'post_title' => $publication->title_uk,
                    'post_date' => $publication->date,
                    'post_content' => $publication->content_uk,
                    'post_excerpt' => $publication->lead_uk,
                    'post_status' =>$active[$publication->active_uk],
                    'post_name'=>$publication->id.'-'.$publication->uri_uk
            ];

            $post_id = wp_insert_post($data);

            $image = $this->dbPublicationsMedia($publication->id);

            $this->saveThumbnail($image,$post_id);

            $dataRu = [
                'post_title' => $publication->title_ru,
                'post_date' => $publication->date,
                'post_content' => $publication->content_ru,
                'post_excerpt' => $publication->lead_ru,
                'post_status' =>$active[$publication->active_ru],
                'post_name'=>$publication->id.'-'.$publication->uri_ru


            ];

            $this->saveTranslationPost($dataRu,$post_id,'ru');

            $dataEn =[
                'post_title' => $publication->title_en,
                'post_date' => $publication->date,
                'post_content' => $publication->content_en,
                'post_excerpt' => $publication->lead_en,
                'post_status' =>$active[$publication->active_en],
                'post_name'=>$publication->id.'-'.$publication->uri_en

            ];

            $this->saveTranslationPost($dataEn,$post_id,'en');

            $dataQt =  [
                'post_title' => $publication->title_qt,
                'post_date' => $publication->date,
                'post_content' => $publication->content_qt,
                'post_excerpt' => $publication->lead_qt,
                'post_status' =>$active[$publication->active_qt],
                'post_name'=>$publication->id.'-'.$publication->uri_qt
            ];

            $this->saveTranslationPost($dataQt,$post_id,'qt');

        }

        return true;
    }


    public function saveTranslationPost($data,$post_id,$code)
    {

        $translation_id = wp_insert_post($data);

        // https://wpml.org/wpml-hook/wpml_element_type/
        $wpml_element_type = apply_filters( 'wpml_element_type', 'post' );
        // get the language info of the original post
        // https://wpml.org/wpml-hook/wpml_element_language_details/
        $get_language_args = array('element_id' => $post_id, 'element_type' => 'post' );
        $original_post_language_info = apply_filters( 'wpml_element_language_details', null, $get_language_args );
        $set_language_args = array(
            'element_id'    =>  $translation_id,
            'element_type'  => $wpml_element_type,
            'trid'   => $original_post_language_info->trid,
            'language_code'   => $code,
            'source_language_code' => $original_post_language_info->language_code
        );

        do_action( 'wpml_set_element_language_details', $set_language_args );

    }





    public  function saveThumbnail($image,$post_id){


        $filename = '/'.$image[0]->path.'/'.$image[0]->name;


        $attachment = array(
            'guid'           => $filename,
            'post_mime_type' => $image[0]->extension,
            'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        $attach_id = wp_insert_attachment( $attachment, $filename, $post_id );


    }

    private  function dbPublicationsMedia(int $publications_id)
    {
        return $this->getDb()->get_results("SELECT * FROM `media` WHERE `publications_id` ='.$publications_id.' ");
    }


}