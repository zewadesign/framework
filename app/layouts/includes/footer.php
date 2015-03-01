
<div class="container">
    <div class="row">
    </div>
</div>

<script src="<?=baseURL('resources/shared/js/jquery/jquery-1.8.2.min.js');?>"></script>
<script src="<?=baseURL('resources/shared/js/jquery/jquery.validate.min.js');?>"></script>
<script src="<?=baseURL('resources/shared/js/bootstrap/bootstrap-3.0.0.min.js');?>"></script>

<script>baseURL = '<?=baseURL();?>';</script>

<?php
$scripts = core\Registry::get('_scripts');
if(!empty($scripts)) :
    foreach($scripts as $script):
        echo "<script src=\"".baseURL('resources/'.$script)."\" type=\"text/javascript\"></script>";
    endforeach;
endif;?>

</body>
</html>