<?php 
    global $wpdb;
    $type = isset($_GET['type']) ? $_GET['type'] : 'pf'; 
    $table_countries = $wpdb->prefix . 'bniu_countries';
    $countries = $wpdb->get_results("SELECT * FROM $table_countries");
?>
<form class="form-certificado" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="guardar_informacion_certificado">
    <input type="hidden" name="order_id" value="<?php echo esc_attr($_GET['order_id']); ?>">
    <h4>Datos Personales</h4>
    <div class="row cols-2">
        <div>
            <label for="document_type">Tipo de documento:</label>
            <select name="document_type" id="document_type" required>
                <option value="cedula">Cedula</option>
                <option value="pasaporte">Pasaporte</option>
            </select>
        </div>
        <div>
            <label for="document_number">Número de documento:</label>
            <input type="text" name="document_number" required>
        </div>
    </div>
    <div class="row cols-3">
        <div>
            <label for="applicant_name">Nombres:</label>
            <input type="text" name="applicant_name" required>
        </div>
        <div>
            <label for="applicant_lastname">1er Apellido:</label>
            <input type="text" name="applicant_lastname" required>
        </div>
        <div>
            <label for="applicant_lastname2">2do Apellido:</label>
            <input type="text" name="applicant_lastname2">
        </div>
    </div>
    <div class="row cols-3">
        <div>
            <label for="applicant_birthdate">Fecha de Nacimiento:</label>
            <input type="date" name="applicant_birthdate" required>
        </div>
        <div>
            <label for="applicant_nacionality">Nacionalidad:</label>
            <input type="text" name="applicant_nacionality" required>
        </div>
        <div>
            <label for="applicant_sex">Sexo:</label>
            <select name="applicant_sex" required>
                <option value="masculino">Masculino</option>
                <option value="femenino">Femenino</option>
            </select>
        </div>
    </div>
    <div class=" row cols-2">
        <div>
            <label name="applicant_number">Número de teléfono:</label>
            <input type="tel" name="applicant_number" pattern="[0-9]{1,4}-[0-9]{1,12}" placeholder="Ejemplo: 1234-567890" required>
        </div>
        <div>
            <label for="applicant_email">Correo electrónico:</label>
            <input type="email" name="applicant_email" required>
        </div>
    </div>
    <div class="row cols-2">
        <div>
            <label for="applicant_has_ruc">¿Con RUC?</label>
            <select name="applicant_has_ruc" id="applicant_has_ruc" required>
                <option value="si">Si</option>
                <option value="no">No</option>
            </select>
        </div>
        <div id="ruc_fields" style="display: none;">
            <label for="applicant_ruc">RUC:</label>
            <input type="text" name="applicant_ruc" required>
        </div>
    </div>
    <?php
    if($type == 'pf'){
    ?>
        <h5>Dirección Domicilio</h5>
        <div class="row cols-2">
            <div>
                <label for="applicant_address">Dirección:</label>
                <input type="text" name="applicant_address" required>
            </div>
            <div>
                <label for="applicant_country">País:</label>
                <select name="applicant_country" required>
                    <?php foreach ($countries as $country) {?>
                        <option value="<?php echo $country->sortname; ?>"><?php echo $country->name; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
    <?php } ?>
    
    <?php if ($type == 'pfbusiness') {?>
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
                <label for="business_area">Area:</label>
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
                    <option value="cedula">Cedula</option>
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
        <h5>Dirección de la Empresa</h5>
        <div class="row cols-2">
            <div>
                <label for="business_address">Dirección:</label>
                <input type="text" name="business_address" required>
            </div>
        </div>
        <div class="row cols-3">
            <div>
                <label for="business_country">País:</label>
                <select id="business_country" name="business_country" required>
                    <option value="" selected disabled>Seleccione un país</option>
                    <?php
                    foreach ($countries as $country) {
                    ?>
                        <option value="<?php echo $country->id; ?>"><?php echo $country->name; ?></option>
                    <?php
                    }
                    ?>
                </select>
            </div>
            <div>
                <label for="business_state">Estado:</label>
                <select id="business_state" name="business_state" required disabled>
                    <option value="" selected disabled>Seleccione un estado</option>
                </select>
            </div>
            <div>
                <label for="business_city">Ciudad:</label>
                <select id="business_city" name="business_city" disabled>
                    <option value="" selected disabled>Seleccione una ciudad</option>
                </select>
            </div>
        </div>
    <?php } ?>

    <?php if ($type == 'rep') {?>
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
        <h5>Dirección de la Empresa</h5>
        <div class="row cols-2">
            <div>
                <label for="business_address">Dirección:</label>
                <input type="text" name="business_address" required>
            </div>
        </div>
        <div class="row cols-3">
            <div>
                <label for="business_country">País:</label>
                <select id="business_country" name="business_country" required>
                    <option value="" selected disabled>Seleccione un país</option>
                    <?php
                    foreach ($countries as $country) {
                    ?>
                        <option value="<?php echo $country->id; ?>"><?php echo $country->name; ?></option>
                    <?php
                    }
                    ?>
                </select>
            </div>
            <div>
                <label for="business_state">Estado:</label>
                <select id="business_state" name="business_state" required disabled>
                    <option value="" selected disabled>Seleccione un estado</option>
                </select>
            </div>
            <div>
                <label for="business_city">Ciudad:</label>
                <select id="business_city" name="business_city" disabled>
                    <option value="" selected disabled>Seleccione una ciudad</option>
                </select>
            </div>
        </div>
    <?php } ?>

    <h5>Fotos y documentos</h5>
    <div class="row cols-3">
        <div>
            <label for="applicant_photo_id_front">Cédula o Pasaporte Frontal:</label>
            <div class="photo_or_upload">
                <div class="photo-button">
                    <button type="button" class="btn btn-primary" data-document="applicant_photo_id_front">Tomar Foto</button>
                </div>
                <div class="upload-button">
                    <button type="button" class="btn btn-primary" data-document="applicant_photo_id_front">Subir Foto</button>
                </div>
            </div>
            <input type="hidden" name="applicant_photo_id_front" required>
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
            <input type="hidden" name="applicant_photo_id_back" required>
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
            <input type="hidden" name="applicant_photo_selfie" required>
        </div>
    </div>

    <?php if ($type == 'pfbusiness' || $type == "rep") {?>
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
                <input type="hidden" name="business_photo_ruc" required>
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
                <input type="hidden" name="business_photo_constitution" required>
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
                <input type="hidden" name="business_photo_representative_appointment" required>
            </div>
        </div>
        <div class="row cols-3">
            <div>
                <label for="business_photo_acceptance_appointment">Aceptación del nombramiento:</label>
                <div class="photo_or_upload">
                    <div class="photo-button">
                        <button type="button" class="btn btn-primary" data-document="business_photo_acceptance_appointment">Tomar Foto</button>
                    </div>
                    <div class="upload-button">
                        <button type="button" class="btn btn-primary" data-document="business_photo_acceptance_appointment">Subir Foto</button>
                    </div>
                </div>
                <input type="hidden" name="business_photo_acceptance_appointment" required>
            </div>
            <?php if ($type == 'pfbusiness') {?>
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
                    <input type="hidden" name="business_photo_representative_dni" required>
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
                    <input type="hidden" name="business_photo_representative_autorization" required>
                </div>
            <?php } ?>
        </div>
    <?php } ?>

    <h4>Formato y tiempo de vigencia</h4>
    <div class="row cols-2">
        <div>
            <label for="format">Formato:</label>
            <select name="format" id="format" required>
                <option value="p12">Archivo .P12</option>
                <option value="cloud">Nube</option>
            </select>
        </div>
        <div>
            <label for="validity">Vigencia del Certificado:</label>
            <select name="validity" id="validity" required>
                <option value="1">1 año</option>
                <option value="2">2 años</option>
                <!--<option value="3">3 años</option>
                <option value="4">4 años</option>
                <option value="5">5 años</option> -->
            </select>
        </div>
    </div>
    <p>Valor a Pagar: 0,00</p>
    <div class="checkbox-group">
        <input type="checkbox" name="policies" id="policies" required>
        <label for="policies">Acepto las <a href="<?php echo get_the_privacy_policy_link(); ?>" target="_blank">Politicas de Privacidad</a></label>
    </div>
    <input type="submit" name="registro" value="Guardar Informacion Certificado">
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
            <canvas id="canvas" width="100%" height="100%"></canvas>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" id="save-photo">Guardar Foto</button>
        </div>
    </div>
</div>
<script>
    (function ($) {
        function dataURItoBlob(dataURI){
            var binary=atob(dataURI.split(',')[1]);
            var array=[];
            for(i=0;i<binary.length;i++){
                array.push(binary.charCodeAt(i));
            }
            return new Blob([new Uint8Array(array)],{type:'image/png'});
        }
        function FileListItems(file_objects){
            new_input=new ClipboardEvent("").clipboardData||new DataTransfer()
            for(i=0,size=file_objects.length;i<size;++i){
                new_input.items.add(file_objects[i]);
            }
            return new_input.files;
        }      
        $(document).ready(function () {
            $('#applicant_has_ruc').on('change', function () {
                if ($(this).val() === 'si') {
                    $('#ruc_fields').show();
                } else {
                    $('#ruc_fields').hide();
                }
            });
            $('.photo-button button').on('click', function () {
                var documentName = $(this).data('document');
                $('#modal-photo').show();
                var video = document.getElementById('video');
                var canvas = document.getElementById('canvas');
                var snap = document.getElementById('snap');
                var savePhoto = document.getElementById('save-photo');
                var context = canvas.getContext('2d');
                var constraints = {
                    audio: false,
                    video: {
                        width: 700, height: 700
                    }
                };
                navigator.mediaDevices.getUserMedia(constraints).then(function (stream) {
                    video.srcObject = stream;
                });
                snap.addEventListener('click', function () {
                    context.drawImage(video, 0, 0, 640, 480);
                });
                var parent = $(this).parent().parent();
                savePhoto.addEventListener('click', function () {
                    $('#'+'img-'+documentName).remove();
                    var data = canvas.toDataURL('image/png');
                    var img = document.createElement('img');
                    img.src = data;
                    img.width = 200;
                    img.height = 150;
                    img.classList.add('photo');
                    img.id = 'img-'+documentName;
                    parent.before(img);
                    /*
                    blob=(dataURItoBlob(data));
                    file=new File([blob],"img.png",{type:"image/png",lastModified:new Date().getTime()});
                    array_images=[file]; 
                    input_images=$('input[name="' + documentName + '"]')[0];
                    input_images.files=new FileListItems(array_images);
                    */
                    $('input[name="' + documentName + '"]').val(data);
                    $('#modal-photo').hide();
                });
            });
            $('#modal-photo .close').on('click', function () {
                $('#modal-photo').hide();
            });

            $('.upload-button button').on('click', function () {
                var documentName = $(this).data('document');
                var input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/*';
                input.click();
                var parent = $(this).parent().parent();
                input.onchange = function () {
                    var file = input.files[0];
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        $('#'+'img-'+documentName).remove();
                        var img = document.createElement('img');
                        img.src = e.target.result;
                        img.width = 200;
                        img.height = 150;
                        img.classList.add('photo');
                        img.id = 'img-'+documentName;
                        parent.before(img);
                        $('input[name="' + documentName + '"]').val(e.target.result);
                    };
                    reader.readAsDataURL(file);
                };
            });
            // request camera access
            navigator.mediaDevices.getUserMedia({ video: true })
            .then(function(stream) {
                console.log('Access to camera granted');
            })
            .catch(function(err) {
                console.log('Access to camera denied');
            });

            $('#business_country').on('change', function () {
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
                    success: function (response) {
                        // create options of json response
                        var states = JSON.parse(response);
                        var options = '';
                        options += '<option value="" selected disabled>Seleccione un estado</option>';
                        states.forEach(function (state) {
                            options += '<option value="' + state.id + '">' + state.name + '</option>';
                        });
                        $('#business_state').html(options);
                        setTimeout(function(){
                            $('#business_state').prop("disabled", false);
                        }, 500);
                    }
                });
            });

            $('#business_state').on('change', function () {
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
                    success: function (response) {
                        // create options of json response
                        var cities = JSON.parse(response);
                        var options = '';
                        options += '<option value="" selected disabled>Seleccione una ciudad</option>';
                        cities.forEach(function (city) {
                            options += '<option value="' + city.id + '">' + city.name + '</option>';
                        });
                        $('#business_city').html(options);
                        setTimeout(function(){
                            $('#business_city').prop("disabled", false);
                        }, 500);
                    }
                });
            });
        
        });
        
    })(jQuery);
</script>