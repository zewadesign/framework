<?php include('includes/header.php'); ?>
<div id="maincontainer">

    <div class="container">
        <br>

        <h2 class="error404"><span>Error 404!</span></h2> <br><br>


        <div class="container centeralign">

            <img src="<?= core\Registry::baseURL('resources/shared/themes/' . core\Registry::get('_license')->alias . '/img/error-404.png'); ?>" alt="" title="">

            <br>   <br>   <br>
            <h2 class="font48">  We are Sorry!</h2>
            <h2>The page you're looking for could not be found.</h2>

        </div>

    </div>

</div>
<?php include('includes/footer.php'); ?>
