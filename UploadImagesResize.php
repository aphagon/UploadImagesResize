<?php 
/**
 * @author : Aphagon Phromdesarn
 * @link   : https://aphagon.me
 * @since 0.1.0
 * Resize Images
 */

class UploadImagesResize {

    /**
     * @var string The new image name.
     */
    protected $file_name;

    /**
     * @var bool The id file.
     */
    protected $on_file_id = null;

    /**
     * @var int The image width in pixels
     */
    protected $width = 150;

    /**
     * @var int The image height in pixels
     */
    protected $height = 150;

    /**
     * @var string The folder or image storage path
     */
    protected $path;

    /**
     * @var int The image max bytes 2MB.
     */
    protected $total_bytes = ( ( 1024 * 1024 ) * 2 );

    /**
     * @var array The mime types allowed for upload.
     */
    protected $mimeTypes = array( 'jpeg', 'png', 'gif', 'jpg', 'pjpeg' );

    /**
     * @var array storage for the global array.
     */
    protected $_files = array( );

    /**
     * @var array get files data.
     */
    protected $fileData = array( );

    /**
     * @var array storage for any alertMessage.
     */
    private $alertMessage = array( );
    
    /**
     * @var array lang
     */
    private $lang = array(
        'errors' => array(
            'mimeTypes'         => '%s - Invalid File! Only (%s) image types are allowed',
            'size'              => '%s - File size is larger than the %s',
            'empty_file'        => 'Please, Select file(s) to upload.',
            'upload'            => '%s Unable to upload file.',
            'permission_folder' => 'Can not create a directory \'%s\' , please check write permission',
            'directory'         => 'Error! directory \'%s\' could not be created'
        ),
        'success' => 'Success File Image: %s'
    );

    /**
     * @param array $files Represents the $_FILES array passed as dependency
     * @param string $keyname $_FILE[ $keyname ]
     */
    public function __construct( Array $files = array( ), $keyname = null ) {
        if ( isset( $keyname ) ) {
            $this->_files[ $keyname ] = $files[ $keyname ];
        } else {
            $this->_files = $files;
        }
        return $this->_files;
        $this->alertMessage[ 'success' ] = array( );
        $this->alertMessage[ 'errors' ]  = array( );
    }

    /**
     * Validate directory/permission before creating a folder.
     *
     * @param $dir string the folder name to check
     *
     * @return bool
     */
    private function isDirectoryValid( $dir ) {
        return ! file_exists( $dir ) && ! is_dir( $dir ) || is_writable( $dir );
    }

    /**
     * @return int formatBytes
     */
    private function formatBytes( ) { 
        $units = array( 'B', 'KB', 'MB', 'GB', 'TB' ); 
        $bytes = max( $this->total_bytes, 0 ); 
        $pow   = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) ); 
        $pow   = min( $pow, count( $units ) - 1 ); 
        return round( $bytes, 0 ) . ' ' . $units[ $pow ]; 
    }

    /**
     * Returns the storage / folder name.
     *
     * @return string
     */
    public function getPath( ) {
        if ( ! $this->path ) {
            $this->setPath( dirname(__FILE__) . '/images' );
        }
        return $this->path;
    }

    /**
     * Returns Error or Success upload 
     * 
     * @return array|false
     */
    public function getReturn ( ) {
        return $this->alertMessage;
    }
    
    /**
     * Returns a JSON format of the images.
     *
     * @return string
     */
    public function getJson( ) {
        return json_encode( $this->getReturn( ) );
    }

    /**
     * Creates a Path for upload storage.
     *
     * @param $path string the folder name to create
     * @param int $permission chmod permission
     *
     * @return $this
     */
    public function setPath( $path = 'images', $permission = 0777 ) {
        if ( ! $this->isDirectoryValid( $path ) ) {
            $this->alertMessage[ 'errors' ] = array( sprintf( $this->lang[ 'errors' ][ 'permission_folder' ], $path ) );
            return false;
        }
        $create = ! is_dir( $path ) ? @mkdir( '' . $path, (int) $permission, true ) : true;
        if ( ! $create ) {
            $this->alertMessage[ 'errors' ] = array( sprintf( $this->lang[ 'errors' ][ 'directory' ], $path ) );
            return false;
        }
        $this->path = $path;
        return $this;
    }
    
    /**
     * Image name if not provided.
     *
     * @param bool $isName
     * @param bool $single
     *
     * @return $this
     */
    public function setFilename( $isName = null, $single = null ) {
        $this->on_file_id = $single;
        if ( ! empty( $isName ) ) {
            $this->file_name = filter_var( $isName, FILTER_SANITIZE_STRING );
        } else {
            $this->file_name = uniqid( '', true ) . '_' . str_shuffle( implode( range( 'e', 'q' ) ) );
        }

        return $this;
    }

    /**
     * @param int $bytes
     */
    public function setBytes( $bytes ) {
        $this->total_bytes = $bytes;
        return $this;
    }

    /**
     * @param int $width image width in pixels
     */
    public function setWidth( $width ) {
        $this->width = $width;
        return $this;
    }

    /**
     * @param int $height image height in pixels
     */
    public function setHeight( $height ) {
        $this->height = $height;
        return $this;
    }

    /**
     * @param array $lang
     */
    public function setLang( $lang ) {
        if ( is_array( $lang ) ) {
            foreach ( $lang as $key => $value ) {
                if ( $key == 'errors' ) {
                    if ( isset( $value ) && is_array( $value ) ) {
                        foreach ( $value as $key2 => $value2 ) {
                            if ( in_array( $key2, array( 'mimeTypes', 'size', 'empty_file', 'permission_folder', 'directory' ) ) ) {
                                $this->lang[ 'errors' ][ $key2 ] = $value2;
                            }
                        }
                    }
                } elseif ( $key == 'success' ) {
                    $this->lang[ 'success' ] = $value;
                }
            }
        }
        return $this;
    }

    /**
     * Upload image to folder.
     * 
     * @return array $this
     */
    public function upload( ) {

        if ( ! empty( $this->_files ) ) {
            foreach ( $this->_files as $name => $type ) {
                if ( count( $type ) !== 5 )
                    continue;

                if ( ! is_array( $type[ 'tmp_name' ] ) ) {
                    $this->fileData[ $name ][ 'name' ][]     = $type[ 'name' ];
                    $this->fileData[ $name ][ 'type' ][]     = $type[ 'type' ];
                    $this->fileData[ $name ][ 'tmp_name' ][] = $type[ 'tmp_name' ];
                    $this->fileData[ $name ][ 'error' ][]    = $type[ 'error' ];
                    $this->fileData[ $name ][ 'size' ][]     = $type[ 'size' ];
                } else {
                    $this->fileData[ $name ] = $type;
                }

                foreach ( $this->fileData[ $name ][ 'tmp_name' ] as $id => $tmp_name ) {
                    
                    if ( ! $this->fileData[ $name ][ 'tmp_name' ][ $id ] )
                        continue;

                    $image_name = $this->fileData[ $name ][ 'name' ][ $id ];
                    $image_size = $this->fileData[ $name ][ 'size' ][ $id ];
                    $image_tmp_name = $this->fileData[ $name ][ 'tmp_name' ][ $id ];

                    $mime = pathinfo( $image_name, PATHINFO_EXTENSION );
                    $mime = strtolower( $mime );

                    if ( ! in_array( $mime, $this->mimeTypes ) ) {
                        $this->alertMessage[ 'errors' ][ $id ] = sprintf( $this->lang[ 'errors' ][ 'mimeTypes' ], $image_name, implode( ', ', $this->mimeTypes ) );
                    } elseif ( $image_size > $this->total_bytes ) {
                        $this->alertMessage[ 'errors' ][ $id ] = sprintf( $this->lang[ 'errors' ][ 'size' ], $image_name, $this->formatBytes( ) );
                    } else {
                        $filename = ( $this->on_file_id ? $this->file_name : $this->file_name . '_' . $id ) . '.' . $mime;
                        $fullPath = rtrim( $this->getPath( ), '\/' ) . '/' . $filename;

                        list( $width, $height ) = getimagesize( $image_tmp_name );
                        if ( move_uploaded_file( $image_tmp_name, $fullPath ) ) {
                            $this->alertMessage[ 'success' ][ $id ] = array(
                                'file_data' => array(
                                    'name'      => $image_name,
                                    'new_name'  => $filename,
                                    'full_path' => $fullPath,
                                    'mime'      => $mime,
                                    'width'     => $width,
                                    'height'    => $height,
                                    'size'      => $type[ 'size' ],
                                ),
                                'keyname' => $name, 
                                'message' => sprintf( $this->lang[ 'success' ], $image_name ),
                            );
                        } else {
                            $this->alertMessage[ 'errors' ][ $id ] = sprintf( $this->lang[ 'errors' ][ 'upload' ], $image_name );
                        }
                    }
                } // End foreach lv2;
            } // End foreach lv1;
        } else {
            $this->alertMessage[ 'errors' ] = array( $this->lang[ 'errors' ][ 'empty_file' ] );
        } // End If;

        return $this;
    }

    /**
     * @return array $this
     */
    public function resize( ) {
        if ( ! empty( $this->alertMessage[ 'success' ] ) ) {
            foreach ( $this->alertMessage[ 'success' ] as $id => $value ) {

                // get file image.
                $imgData = $value[ 'file_data' ];

                // First, calculate the height.
                $height = intval( $this->width / $imgData[ 'height' ] * $imgData[ 'width' ] );

                // If the height is too large, set it to the maximum height and calculate the width.
                if ( $height > $this->height ) {
                    $height = $this->height;
                    $this->width = intval( $height / $imgData[ 'height' ] * $imgData[ 'width' ] );
                }

                $source_aspect_ratio = ( $imgData[ 'width' ] / $imgData[ 'height' ] );
                $thumbnail_aspect_ratio = ( $this->width / $this->height );
                if ( $source_aspect_ratio > $thumbnail_aspect_ratio ) {
                    $this->width  = ( int ) ( $this->height * $source_aspect_ratio );
                    $this->height = $this->height;
                } else {
                    $this->width  = $this->width;
                    $this->height = ( int ) ( $this->width / $source_aspect_ratio );
                }
                
                $src_img = imagecreatefromstring( file_get_contents( $imgData[ 'full_path' ] ) );
                $tmp = imagecreatetruecolor( $this->width, $this->height );
                imagealphablending( $tmp, false );
                imagesavealpha( $tmp, true );
                imagecopyresampled( $tmp, $src_img, 0, 0, 0, 0, $this->width, $this->height, $imgData[ 'width' ], $imgData[ 'height' ] );

                if ( in_array( $imgData[ 'mime' ], array( 'jpeg', 'jpg', 'pjpeg' ) ) ) {
                    imagejpeg( $tmp, $imgData[ 'full_path' ], 90 );
                } elseif ( $imgData[ 'mime' ] == 'png' ) {
                    imagepng( $tmp, $imgData[ 'full_path' ], 0 );
                } elseif ( $imgData[ 'mime' ] == 'gif' ) {
                    imagegif( $tmp, $imgData[ 'full_path' ] );
                } else {
                    $this->alertMessage[ 'errors' ][ $id ] = 'Only jpg, jpeg, png and gif files can be resized';
                }

                imagedestroy( $src_img );
                imagedestroy( $tmp );

            } // End foreach;
        } // End if;

        return $this;
    }

}

?>
