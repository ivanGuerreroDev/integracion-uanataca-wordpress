<?php
global $wpdb;
//default
$type_formulario = isset($_GET['type']) ? $_GET['type'] : 'fce';
$producto_certificado_id = 0;
$formato_str ="";
if($type_formulario == 'fec'){
    $producto_certificado_id =get_option('certificado_pf_firma_electronica_calificada');
    $formato_str ="cloud";
    $type = "pf";
}
if($type_formulario == 'fef'){
    $producto_certificado_id =get_option('certificado_pf_firma_electronica_facturar');
    $formato_str ="software";
    $type = "pf";
}
$table_countries = $wpdb->prefix . 'bniu_countries';
$countries = $wpdb->get_results("SELECT * FROM $table_countries");
$currency_code = get_woocommerce_currency();
$currency_symbol = get_woocommerce_currency_symbol();
$flow_type = get_option('unataca-workflow');

$meta_key = 'profile'; // formato
$profile = get_post_meta($producto_certificado_id, $meta_key, true);
?>
<form id="certRequestForm" class="form-certificado" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="add_to_cart_cert_data">
    <input type="hidden" name="applicant_type" value="<?php echo $type; ?>">
    <input type="hidden" id="producto_certificado_id" value="<?php echo $producto_certificado_id;?>">
    <h4>Datos Personales</h4>
    <div class="row cols-3">
        <div class='mobile-complete'>
            <label for="document_type">Tipo de documento:</label>
            <select name="document_type" id="document_type" required>
                <option value="cedula">Cédula</option>
                <option value="pasaporte">Pasaporte</option>
            </select>
        </div>
        <div class='mobile-complete'>
   <label for="document_country">País de emisión:</label>
    <select name="document_country">
        <?php 
            foreach ($countries as $country) {
                $selected = ($country->sortname == 'PA') ? 'selected' : '';
                echo "<option value='{$country->sortname}' $selected>{$country->name}</option>";
            }
        ?>
    </select>
            <!--<select name="document_country" required disabled="disabled">                
                <option selected="selected" value="<?php echo $option_select->sortname; ?>"><?php echo $option_select->name; ?></option>                
            </select>-->
        </div>
        <div class='mobile-complete'>
            <label for="document_number">Número de documento:</label>
            <input type="text" name="document_number" required>
            <span class="info-text">El documento se debe colocar con guiones</span>
        </div>
    </div>
    <div class="row cols-4">
        <div class='mobile-complete'>
            <label for="applicant_name">1er Nombre:</label>
            <input type="text" name="applicant_name" required>
        </div>
        <div class='mobile-complete'>
            <label for="applicant_second_name">2do Nombre:</label>
            <input type="text" name="applicant_second_name">
        </div>
        <div class='mobile-complete'>
            <label for="applicant_lastname">1er Apellido:</label>
            <input type="text" name="applicant_lastname" required>
        </div>
        <div class='mobile-complete'>
            <label for="applicant_lastname_two">2do Apellido:</label>
            <input type="text" name="applicant_lastname_two">
        </div>
    </div>
    <div class="row cols-3">
        <div class='mobile-complete'>
            <label for="applicant_birthdate">Fecha de Nacimiento:</label>
            <input 
                required
                type="date" 
                name="applicant_birthdate"
                max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>"
            />
        </div>
    </div>
    <div class=" row cols-2">
        <div class='mobile-complete'>
            <label name="applicant_number">Número de teléfono:</label>
            <input id="phone" type="tel" name="applicant_number" required>
        </div>
        <div class='mobile-complete'>
            <label for="applicant_email">Correo electrónico:</label>
            <input type="email" name="applicant_email" required>
        </div>
    </div>
    <?php if((int)$flow_type == 1){?>
        <h5>Fotos y documentos</h5>
        <div class="row cols-3">
            <div>
                <label for="applicant_photo_id_front">Cédula o Pasaporte Frontal:</label>
                <div class="photo_or_upload" >
                    <div class="photo-button">
                        <button type="button" class="btn btn-primary" data-document="applicant_photo_id_front">Tomar Foto</button>
                    </div>
                    <div class="upload-button">
                        <button type="button" class="btn btn-primary" data-document="applicant_photo_id_front">Subir Foto</button>
                    </div>
                </div>
                <input type="text" class="b64-hidden" name="applicant_photo_id_front" required>
            </div>
            <div>
                <label for="applicant_photo_id_back">Cédula o Pasaporte Posterior:</label>
                <div class="photo_or_upload">
                    <div class="photo-button">
                        <button type="button" class="btn btn-primary" data-document="applicant_photo_id_back">Tomar Foto</button>
                    </div>
                    <div class="upload-button">
                        <button type="button" class="btn btn-primary" data-document="applicant_photo_id_back">Subir Foto</button>
                    </div>
                </div>
                <input type="text" class="b64-hidden" name="applicant_photo_id_back" required>
            </div>
            <div>
                <label for="applicant_photo_selfie">Selfie con Cédula o Pasaporte:</label>
                <div class="photo_or_upload">
                    <div class="photo-button">
                        <button type="button" class="btn btn-primary" data-document="applicant_photo_selfie">Tomar Foto</button>
                    </div>
                    <div class="upload-button">
                        <button type="button" class="btn btn-primary" data-document="applicant_photo_selfie">Subir Foto</button>
                    </div>
                </div>
                <input type="text" class="b64-hidden" name="applicant_photo_selfie" required>
            </div>
        </div>
    <?php } ?>
    <!--<div class="row cols-2">
        <div>
            <label for="applicant_has_ruc">¿Con RUC?</label>
            <select name="applicant_has_ruc" id="applicant_has_ruc" required>
                <option value="" selected disabled>Seleccione una opción</option>
                <option value="si">Si</option>
                <option value="no">No</option>
            </select>
        </div>
        <div id="ruc_fields" style="display: none;">
            <label for="applicant_ruc">RUC:</label>
            <input type="text" name="applicant_ruc" required>
        </div>
    </div> -->

    <?php if ($type == 'pfbusiness') { ?>
        <h5>Datos de la empresa</h5>
        <div class="row cols-2">
            <div>
                <label for="business_ruc">Ruc:</label>
                <input type="text" name="business_ruc" required>
            </div>
            <div>
                <label for="business_name">Razón social:</label>
                <input type="text" name="business_name" required>
            </div>

        </div>
        <div class="row cols-2">
            <div>
                <label for="business_area">Área:</label>
                <input type="text" name="business_area" required>
            </div>
            <div>
                <label for="business_charge">Cargo del Solicitante:</label>
                <input type="text" name="business_charge" required>
            </div>
        </div>
        <h5>Representante Legal</h5>
        <div class="row cols-2">
            <div>
                <label for="rep_document_type">Tipo de documento:</label>
                <select name="rep_document_type" id="rep_document_type" required>
                    <option value="cedula">Cédula</option>
                    <option value="pasaporte">Pasaporte</option>
                </select>
            </div>
            <div>
                <label for="rep_document_number">Número de documento:</label>
                <input type="text" name="rep_document_number" required>
            </div>
        </div>
        <div class="row cols-3">
            <div>
                <label for="rep_name">Nombres:</label>
                <input type="text" name="rep_name" required>
            </div>
            <div>
                <label for="rep_lastname">1er Apellido:</label>
                <input type="text" name="rep_lastname" required>
            </div>
            <div>
                <label for="rep_lastname2">2do Apellido:</label>
                <input type="text" name="rep_lastname2">
            </div>
        </div>
    <?php } ?>

    <?php if ($type == 'rep') { ?>
        <h5>Datos de la empresa</h5>
        <div class="row cols-3">
            <div>
                <label for="business_ruc">Ruc:</label>
                <input type="text" name="business_ruc" required>
            </div>
            <div>
                <label for="business_name">Razón social:</label>
                <input type="text" name="business_name" required>
            </div>
            <div>
                <label for="business_charge">Cargo del Solicitante:</label>
                <input type="text" name="business_charge" required>
            </div>
        </div>
    <?php } ?>
    
    <?php if ($type == 'pfbusiness' || $type == "rep") { ?>
        <h5>Documentos Necesarios</h5>
        <div class="row cols-3">
            <div>
                <label for="business_photo_ruc">Copia del Ruc de la Empresa:</label>
                <div class="photo_or_upload">
                    <div class="photo-button">
                        <button type="button" class="btn btn-primary" data-document="business_photo_ruc">Tomar Foto</button>
                    </div>
                    <div class="upload-button">
                        <button type="button" class="btn btn-primary" data-document="business_photo_ruc">Subir Foto</button>
                    </div>
                </div>
                <input type="text" class="b64-hidden" name="business_photo_ruc" required>
            </div>
            <div>
                <label for="business_photo_constitution">Constitución de la compañia:</label>
                <div class="photo_or_upload">
                    <div class="photo-button">
                        <button type="button" class="btn btn-primary" data-document="business_photo_constitution">Tomar Foto</button>
                    </div>
                    <div class="upload-button">
                        <button type="button" class="btn btn-primary" data-document="business_photo_constitution">Subir Foto</button>
                    </div>
                </div>
                <input type="text" class="b64-hidden" name="business_photo_constitution" required>
            </div>
            <div>
                <label for="business_photo_representative_appointment">Nombramiento del representante:</label>
                <div class="photo_or_upload">
                    <div class="photo-button">
                        <button type="button" class="btn btn-primary" data-document="business_photo_representative_appointment">Tomar Foto</button>
                    </div>
                    <div class="upload-button">
                        <button type="button" class="btn btn-primary" data-document="business_photo_representative_appointment">Subir Foto</button>
                    </div>
                </div>
                <input type="text" class="b64-hidden" name="business_photo_representative_appointment" required>
            </div>
        </div>
        <div class="row cols-3">
            <?php if ($type == 'pfbusiness') { ?>
                <div>
                    <label for="business_photo_representative_dni">Cédula del representante:</label>
                    <div class="photo_or_upload">
                        <div class="photo-button">
                            <button type="button" class="btn btn-primary" data-document="business_photo_representative_dni">Tomar Foto</button>
                        </div>
                        <div class="upload-button">
                            <button type="button" class="btn btn-primary" data-document="business_photo_representative_dni">Subir Foto</button>
                        </div>
                    </div>
                    <input type="text" class="b64-hidden" name="business_photo_representative_dni" required>
                </div>
                <div>
                    <label for="business_photo_representative_autorization">Autorización del representante:</label>
                    <div class="photo_or_upload">
                        <div class="photo-button">
                            <button type="button" class="btn btn-primary" data-document="business_photo_representative_autorization">Tomar Foto</button>
                        </div>
                        <div class="upload-button">
                            <button type="button" class="btn btn-primary" data-document="business_photo_representative_autorization">Subir Foto</button>
                        </div>
                    </div>
                    <input type="text" class="b64-hidden" name="business_photo_representative_autorization" required>
                </div>
            <?php } ?>
        </div>
    <?php } ?>

    <h4>Tiempo de vigencia</h4>
    <div class="row cols-2">
       <!--<div>
            <label for="format">Formato:</label>

            <select name="format" id="format">
                <option value="<?php echo $formato_str?>" selected="selected" disabled="disabled"><?php echo $formato_str ?></option>
                <?php

                //get variations of product
                $product = wc_get_product($producto_certificado_id);
                $variations = $product->get_available_variations();

                // get products by term selected on option certificate_products_term.
                //$term = get_option('certificate_products_term');
                //$args = array(
                //    'post_type' => 'product',
                //    'posts_per_page' => -1,
                //    'tax_query' => array(
                //        array(
                //            'taxonomy' => 'product_cat',
                //            'field' => 'term_id',
                //            'terms' => $term
                //        )
                //    )
                //);
                //$products = new WP_Query($args);
                //if ($products->have_posts()) {
                    //while ($products->have_posts()) {
                    //    $products->the_post();
                    //    $product = wc_get_product(get_the_ID());
                    //    $variations = $product->get_available_variations();
                ?>
                    <!--<option value="<?php echo get_the_ID(); ?>"><?php echo $product->get_name(); ?></option>
                <?php
                    //}
                //}
                ?>
            </select>
        </div>-->
        <div class='mobile-complete'>
            <label for="validity">Vigencia del Certificado:</label>
            <select name="validity" id="validity" required>
                <option value="" selected disabled>Seleccione una opción</option>
            </select>
        </div>
    </div>
    <p>Valor a Pagar: <strong><span class="amount_to_pay">0.00</span></strong></p>
    <div class="checkbox-group">
        <input type="checkbox" name="policies" id="policies" required>
        <!--<label for="policies">Acepto las <a href="<?php echo esc_attr( esc_url( get_privacy_policy_url() ) ); ?>" target="_blank">Politicas de Privacidad</a></label>-->
        <label for="policies">Acepto las <a href="https://firmatech.io/wp-content/uploads/2024/03/PC_FIRMATECH.v1.1.pdf" target="_blank">Politicas de Privacidad</a></label>
    </div>
    <input type="hidden" name="product_variation_id" value="">
    <input type="submit" name="add_to_cart_cert_data" value="Añadir al carrito">
</form>

<div class="modal" id="modal-photo">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Tomar Foto</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <video id="video" width="100%" height="100%" autoplay></video>
            <button id="snap" class="btn btn-primary">Tomar Foto</button>
            <canvas id="canvas" width="300px" height="300px"></canvas>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" id="save-photo">Guardar Foto</button>
        </div>
    </div>
</div>
<script>
    (function($) {
        function dataURItoBlob(dataURI) {
            var binary = atob(dataURI.split(',')[1]);
            var array = [];
            for (i = 0; i < binary.length; i++) {
                array.push(binary.charCodeAt(i));
            }
            return new Blob([new Uint8Array(array)], {
                type: 'image/png'
            });
        }

        function FileListItems(file_objects) {
            new_input = new ClipboardEvent("").clipboardData || new DataTransfer()
            for (i = 0, size = file_objects.length; i < size; ++i) {
                new_input.items.add(file_objects[i]);
            }
            return new_input.files;
        }
        $(document).ready(function() {
            /*$('#applicant_has_ruc').on('change', function() {
                if ($(this).val() === 'si') {
                    $('#ruc_fields').show();
                } else {
                    $('#ruc_fields').hide();
                }
            });*/
            var documentName = null;
            $('.photo-button button').on('click', function() {
                documentName = $(this).data('document');
                $('#modal-photo').show();
                var video = document.getElementById('video');
                var canvas = document.getElementById('canvas');
                var snap = document.getElementById('snap');
                var savePhoto = document.getElementById('save-photo');
                var context = canvas.getContext('2d');
                var constraints = {
                    audio: false,
                    video: {
                        facingMode: {
                            exact: "user"
                        },
                        width: 700,
                        height: 700
                    }
                };
                navigator.mediaDevices.getUserMedia(constraints).then(function(stream) {
                    video.srcObject = stream;
                });
                snap.addEventListener('click', function() {
                    context.drawImage(video, 0, 0, 700, 700, 0, 0, 300, 300);
                });
                var parent = $(this).parent().parent();
                savePhoto.addEventListener('click', function() {
                    console.log('Remove id ', 'img-' + documentName)
                    var data = canvas.toDataURL('image/png');
                    if ($('#img-' + documentName).length) {
                        $('#img-' + documentName).attr('src', data);
                    } else {
                        var img = document.createElement('img');
                        img.src = data;
                        img.width = 200;
                        img.height = 200;
                        img.classList.add('photo');
                        img.id = 'img-' + documentName;
                        $('label[for=' + documentName + ']').after(img);
                    }
                    $('input[name="' + documentName + '"]').val(data);
                    $('#modal-photo').hide();

                    /*
                    blob=(dataURItoBlob(data));
                    file=new File([blob],"img.png",{type:"image/png",lastModified:new Date().getTime()});
                    array_images=[file]; 
                    input_images=$('input[name="' + documentName + '"]')[0];
                    input_images.files=new FileListItems(array_images);
                    */

                });
            });
            $('#modal-photo .close').on('click', function() {
                $('#modal-photo').hide();
            });

            $('.upload-button button').on('click', function() {
                documentName = $(this).data('document');
                var input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/*';
                input.click();
                var parent = $(this).parent().parent();
                input.onchange = function() {
                    var file = input.files[0];
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        if ($('#img-' + documentName).length) {
                            $('#img-' + documentName).attr('src', e.target.result);
                        } else {
                            var img = document.createElement('img');
                            img.src = e.target.result;
                            img.width = 200;
                            img.height = 200;
                            img.classList.add('photo');
                            img.id = 'img-' + documentName;
                            $('label[for=' + documentName + ']').after(img);
                        }
                        $('input[name="' + documentName + '"]').val(e.target.result);
                    };
                    reader.readAsDataURL(file);
                };
            });
            // request camera access
            /*navigator.mediaDevices.getUserMedia({
                    video: true
                })
                .then(function(stream) {
                    console.log('Access to camera granted');
                })
                .catch(function(err) {
                    console.log('Access to camera denied');
                });*/

            $('#business_country').on('change', function() {
                var country = $(this).val();
                $('#business_state').val('');
                $('#business_state').html('<option value="" selected disabled>Cargando...</option>');
                $('#business_state').prop("disabled", true);
                $('#business_city').val('');
                $('#business_city').html('<option value="" selected disabled>Seleccione una ciudad</option>');
                $('#business_city').prop("disabled", true);
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'post',
                    data: {
                        action: 'get_states',
                        country: country
                    },
                    success: function(response) {
                        // create options of json response
                        var states = JSON.parse(response);
                        var options = '';
                        options += '<option value="" selected disabled>Seleccione un estado</option>';
                        states.forEach(function(state) {
                            options += '<option value="' + state.id + '">' + state.name + '</option>';
                        });
                        $('#business_state').html(options);
                        setTimeout(function() {
                            $('#business_state').prop("disabled", false);
                        }, 500);
                    }
                });
            });

            $('#business_state').on('change', function() {
                var state = $(this).val();
                $('#business_city').val('');
                $('#business_city').html('<option value="" selected disabled>Cargando...</option>');
                $('#business_city').prop("disabled", true);
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'post',
                    data: {
                        action: 'get_cities',
                        state: state
                    },
                    success: function(response) {
                        // create options of json response
                        var cities = JSON.parse(response);
                        var options = '';
                        options += '<option value="" selected disabled>Seleccione una ciudad</option>';
                        cities.forEach(function(city) {
                            options += '<option value="' + city.id + '">' + city.name + '</option>';
                        });
                        $('#business_city').html(options);
                        setTimeout(function() {
                            $('#business_city').prop("disabled", false);
                        }, 500);
                    }
                });
            });

            $('#certRequestForm').validate({
                submitHandler: function(form) {
                    form.submit();
                }
            });
            var prices = [];
            var currency_symbol = '<?php echo $currency_symbol; ?>';
            //$('#format').on('change', function() {
                prices = [];
                var format = $('#producto_certificado_id').val();
                var validity = $('#validity');
                validity.html('<option value="" selected disabled>Seleccione una opción</option>');
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'post',
                    data: {
                        action: 'get_variations',
                        product_id: format
                    },
                    success: function(response) {
                        console.log(response)
                        var variations = response;
                        var options = '';
                        options += '<option value="" selected disabled>Seleccione una opción</option>';
                        variations.forEach(function(variation) {
                            options += '<option value="' + variation.variation_id + '">' + variation.attributes.attribute_vigencia + '</option>';
                            prices[variation.variation_id] = variation.display_price;
                        });
                        validity.html(options);
                        $('.amount_to_pay').html(currency_symbol + "0.00");
                        $('input[name="product_variation_id"]').val(0.00);
                    }
                });
            //});
            $('#validity').on('change', function() {
                console.log(prices)
                var price = prices[$(this).val()];
                $('.amount_to_pay').html(currency_symbol + price);
                $('input[name="product_variation_id"]').val($(this).val());
            });
            const input = document.querySelector("#phone");
            window.intlTelInput(input, {
                utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js",
                i18n: {
                    selectedCountryAriaLabel: 'País seleccionado',
                    countryListAriaLabel: 'Lista de países',
                    searchPlaceholder: 'Buscar',
                },
                initialCountry: "pa",
            });
        });
    })(jQuery);
</script>