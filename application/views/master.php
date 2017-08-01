<html lang="en">
<head>
    <!-- Metadata -->
    <?php $this->load->view('templates/head/metas') ?>

    <!-- Site information -->
    <link rel="icon" href="../../favicon.ico">
    <title><?php echo $title ? $title : 'MopsiCrawler' ?></title>

    <!-- Styles -->
    <?php $this->load->view('templates/head/styles') ?>

    <!-- Controller custom styles -->
    <?php if (isset($css_files)): ?>
        <?php foreach ($css_files as $file): ?>
            <link type="text/css" rel="stylesheet" href="<?php echo $file; ?>"/>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Miscs -->
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>

<!-- Navigation Bar -->
<?php $this->load->view('templates/navigation') ?>

<!-- Header -->
<?php $this->load->view('templates/header') ?>

<!-- Content -->
<div class="container theme-showcase" role="main">
    <?php $this->load->view($content_template, $content_data) ?>
</div> <!-- /container -->

<!-- Footer -->
<?php $this->load->view('templates/footer') ?>

<!-- Scripts -->
<?php $this->load->view('templates/scripts') ?>
<!-- Controller's custom scripts -->
<?php if (isset($js_files)): ?>
    <?php foreach ($js_files as $file): ?>
        <script src="<?php echo $file; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>