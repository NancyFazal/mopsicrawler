<div class="jumbotron">
    <h1>Image Gallery</h1>
    <p>Crawled images will appear here</p>
</div>
<section>
    <div class="container gal-container">
        <?php
        foreach ($images as $image):
            $id = $image['id'];
            $src = $this->config->base_url() . $image['path'];
            $source = $image['source'];
            $alt = $image['alt'];
            $location_name = $image['location_name'];
            $text = $image['related_texts'];
            $width = $image['width'];
            $height = $image['height'];
            $element_class = ($width > 1000 && $height > 1000) ? 'col-md-8 col-sm-12 co-xs-12' : 'col-md-4 col-sm-6 co-xs-12';
            ?>
            <div class="<?php echo $element_class ?> gal-item">
                <div class="box">
                    <a href="#" data-toggle="modal" data-target="#<?php echo $id ?>">
                        <img src="<?php echo $src ?>" alt="<?php echo $alt ?>">
                    </a>
                    <div class="modal fade" id="<?php echo $id ?>" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                            aria-hidden="true">×</span></button>
                                <div class="modal-body">
                                    <img src="<?php echo $src ?>" alt="<?php echo $alt ?>">
                                </div>
                                <div class="col-md-12 description">
                                    <h4><?php echo !empty($text) ? $text : "[No description]" ?></h4>
                                    <div>
                                        <h5><?php echo !empty($location_name) ? $location_name : "[Location Information Unavailable]" ?></h5>
                                        <p><?php echo 'Source: ' . $source ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>