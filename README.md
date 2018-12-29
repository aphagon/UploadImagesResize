# UploadImagesResize
Eaay Library UploadImagesResize

## Usage
สร้างฟอร์มอัปโหลดสำหรับ HTML:

```html
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="upload_image" value=""/>
    <input type="submit" value="Upload File"/>
</form>
```

สำหรับการใช้งานส่งข้อมูลไปยัง server-side สำหรับไฟล์เดียว
```php
<?php 
include_once 'UploadImagesResize.php';

if ( ! empty( $_FILES ) ) {
  $upload = new UploadImagesResize( $_FILES, 'upload_image' );
  $upload->setPath( 'uploads' );
  $upload->upload( );
  $upload->resize( );
}

?>
```
