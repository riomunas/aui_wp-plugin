<?php
// Pastikan tidak ada akses langsung
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<!-- Semantic UI JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.js"></script>

<!-- Semantic UI CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.css">


<style>
    .form-container {
        margin-left: 20%;
        margin-right: 20%;
        margin-top:50px;
        margin-bottom:100px;
    }
    
    @media (max-width: 768px) {
        .form-container {
            margin-left: 10px;
            margin-right: 10px;
        }
    }
    /*.hidden {*/
    /*    display:none;*/
    /*}*/
</style>

<div class="scoped-semantic-ui form-container">
    
    <h1 class="ui header">Registration Form</h1>
    <form id="pendaftaran-mahasiswa" class="ui form" enctype="multipart/form-data" method="post">
        <div class="field required">
            <label>Name</label>
            <input type="text" name="name" placeholder="Name">
        </div>
        <div class="three fields">
            <div class="field required">
                <label>Country</label>
                <select id='countryDropdown' name="country" class="ui dropdown search">
                    <option value="">Select Country</option>
                    <!-- Options fetched dynamically from database -->
                </select>
            </div>
            <div class="field required">
                <label>Place of birth</label>
                <input type="text" name="city_of_birth" placeholder="Place of birth">
            </div>
            <div class="field required">
                <label>Date of birth</label>
                <input type="date" name="date_of_birth">
            </div>
        </div>
        <div class="two fields">
            <div class="field required">
                <label>Degree</label>
                <select id="degreeDropdown" name="degree" class="ui dropdown">
                    <option value="">Select Degree</option>
                </select>
            </div>
            <div class="field required">
                <label>Faculty</label>
                <select id='departmentDropdown' name="faculty" class="ui dropdown search">
                    <option value="">Select Faculty</option>
                    <!-- Options fetched dynamically from database -->
                </select>
            </div>
        </div>
        <div class="field required">
            <label>Program</label>
            <select id="programDropdown" name="program" class="ui dropdown search">
                <option value="">Select Program</option>
                <!-- Options will be populated based on selected Fakultas -->
            </select>
        </div>
        
        <div class="two fields">
            <div class="field required">
                <label>Email</label>
                <input type="email" name="email" placeholder="Email">
            </div>
            <div class="field required">
                <label>Phone Number</label>
                <input type="text" name="phone" placeholder="Nomor HP">
            </div>
        </div>
		<div class="field required">
			<label>Last Certification</label>
			<input id="last_certification" type="file" name="last_certification" accept="image/*" onchange="previewFile(this, 'last-certification-preview')">
			<img id="last-certification-preview" class="hidden ui image" style="max-width: 150px; margin:10px 0px">
		</div>
        <div class="two fields">
            <div class="field required">
                <label>Selfie</label>
                <input id="selfie" type="file" name="selfie" accept="image/*" onchange="previewFile(this, 'foto-preview')">
                <img id="foto-preview" class="hidden ui image" style="max-width: 150px; margin:10px 0px">
            </div>
            <div class="field required">
                <label>Identity Image</label>
                <input type="file" name="identity" accept="image/*" onchange="previewFile(this, 'ktp-preview')">
                <img id="ktp-preview" class="hidden ui image" style="max-width: 150px; margin:10px 0px">
            </div>
        </div>
        
        <div class="field required">
            <label>Address</label>
            <textarea name="address" placeholder="Address"></textarea>
        </div>
        <button type="submit" name="submit_mahsiswaregistration" class="ui primary button">Submit</button>
        <button id="btnReset" type="button" class="ui secondary button">Reset</button>
        
        <!-- loading nya -->
        <div class="ui active inverted dimmer" id="loading-dimmer" style="display: none;">
            <div class="ui text loader">Loading</div>
        </div>
        
        <div class="ui error message"></div>
        
        <div class="ui positive message hidden">
          <i class="close icon"></i>
          <div class="header">
            Congratulations! Your registration is complete
          </div>
          <p>We have sent an email outlining the next steps for your journey with us. Please check your inbox for detailed instructions and important information. We are excited to welcome you and look forward to seeing you soon in class. Let's embark on this educational adventure together</p>
        </div>
        
      </div>
    </form>
</div>

<script>
    $(".close.icon").click(function(){
      $(this).parent().hide();
    });

    function previewFile(input, previewId) {
        var file = input.files[0];
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(previewId).src = e.target.result;
            $(`#${previewId}`).removeClass('hidden');
        }
        reader.readAsDataURL(file);
    }
    
    async function fetchDataInit() {
        try {
            const response = await fetch('<?php echo admin_url('admin-ajax.php?action=auipmt_initdata'); ?>', {
                method: 'GET'
            });
            if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
            }
            return await response.json();
        } catch (error) {
            console.log('Error:', error);
            return null;
        }
    }
    
    $(document).ready(async function() {
        var data =  await fetchDataInit();
        
        const degrees = data.degrees;
        const departments = data.departments;
        const programs = data.programs;
        const countries = data.countries;
		
		const urlParams = new URLSearchParams(window.location.search);
		const selectedDegree = urlParams.get('degree'); // Mendapatkan nilai dari parameter 'degree'
            
        //btnReset
        $('#btnReset').click(() => {
            $('#pendaftaran-mahasiswa').form('clear');
            $('#foto-preview').addClass('hidden');
            $('#ktp-preview').addClass('hidden');
            $('#last-certification-preview').addClass('hidden');
        })
            
        //country
        const countryDropdown = $('#countryDropdown');
        // countryDropdown.append(`<option value="">Select Country</option>`);
        countries.forEach(country => {
            countryDropdown.append(`<option value="${country.id}"><div class="item"> <i class="${country.kode} flag"></i>${country.name}</div></option>`);
        });
        countryDropdown.dropdown('clear')
        
        // degree
        const degreeDropdown = $('#degreeDropdown');
        degrees.forEach(degree => {
            degreeDropdown.append(`<option value="${degree.id}">${degree.kode}</option>`);
        });
		
		console.log(">> selected --> ", selectedDegree);
		if (selectedDegree) {
			$('#degreeDropdown').dropdown('set selected', selectedDegree);
		}
    
        //department
        const departmentDropdown = $('#departmentDropdown');
        // departmentDropdown.append(`<option value="0">Select Faculty</option>`);
        departments.forEach(department => {
            departmentDropdown.append(`<option value="${department.id}">${department.name}</option>`);
        });
        
        const programDropdown = $('#programDropdown');
        programDropdown.dropdown({
			fullTextSearch:true
		});
        
        countryDropdown.dropdown({
           action: function(text, value) {
               countryDropdown.dropdown('set value', value);
               countryDropdown.dropdown('set selected', value);
               countryDropdown.dropdown('hide');
            }
        });
        
          
        // degreeDropdown.dropdown({
        //     action: function(text, value) {
        //         programDropdown.dropdown('change values', []);
        //         departmentDropdown.dropdown('clear');
        //         degreeDropdown.dropdown('set value', value);
        //         degreeDropdown.dropdown('set selected', value);
        //         degreeDropdown.dropdown('hide');
        //     }
        // });
        degreeDropdown.dropdown({
            onChange:function(value, text, $selectedItem) {
                console.log(value, text, $selectedItem);
                programDropdown.dropdown('change values', []);
                departmentDropdown.dropdown('clear');
            }
        });
        
        departmentDropdown.dropdown({
			fullTextSearch:true,
            onChange:function(value, text, $selectedItem) {
                console.log(value, text, $selectedItem);
                const degreeId = degreeDropdown.dropdown('get value');
                programDropdown.dropdown('change values', []);
                $('#programDropdown').empty();
                programs
                    .filter(program => (program.department_id == value && program.degree_id == degreeId))
                    .forEach(program => {
                        programDropdown.append(`<option value="${program.id}">${program.name}</option>`);
                    });
            }
        });
        
        // departmentDropdown.dropdown({
        //     action: function(text, value) {
        //         const degreeId = degreeDropdown.dropdown('get value');
        //         const programDropdown = $('#programDropdown');
        //         programDropdown.dropdown('set value', [])
                
        //         console.log(">> department_id , degree_id ", value, degreeId);
        //         // programDropdown.append(`<option value="0">Select Program</option>`);
        //         // programs
        //         programs
        //             .filter(program => (program.department_id == value && program.degree_id == degreeId))
        //             .forEach(program => {
        //                 programDropdown.append(`<option value="${program.id}">${program.name}</option>`);
        //             });
                
        //         departmentDropdown.dropdown('set value', value);
        //         departmentDropdown.dropdown('set selected', value);
        //         departmentDropdown.dropdown('hide');
        //     }
        //   });
        
        $('#pendaftaran-mahasiswa')
          .form({   
            fields: {
                name : 'empty',
                country : 'empty',
                city_of_birth : 'empty',
                date_of_birth : 'empty',
                degree : 'empty',
                faculty : 'empty',
                program : 'empty',
                email : 'empty',
                phone : 'empty',
				last_certification: 'empty',
                selfie : 'empty',
                identity : 'empty',
                address : 'empty'
            }
          })
        ;           
    
        $('#pendaftaran-mahasiswa').submit(async function(e) {
            e.preventDefault();
            var form = $('#pendaftaran-mahasiswa');
            $('.error.message').hide();
            $('.positive.message').hide();
            
            if (form.form('is valid')) {
                form.addClass('loading');
                $('.dropdown').addClass('disabled')
                $('#pendaftaran-mahasiswa input').attr('readonly', true);
                $('#pendaftaran-mahasiswa button').attr('disabled', true);
                
                var formData = new FormData(this);
                
                try {
                  const response = await fetch('<?= admin_url( 'admin-ajax.php' ).'?action=auipmt_mahasiswaregistration' ?>', {
                      method: 'POST',
                      body: formData,
                      processData: false, // Menonaktifkan pemrosesan data
                      contentType: false, // Menonaktifkan header konten
                    })
                    
                  const result = await response.json();
                  if (response.ok) {
                    console.log({ result });
                      
                      //reset form
                    $('#pendaftaran-mahasiswa').form('clear');
                    $('#foto-preview').addClass('hidden');
                    $('#ktp-preview').addClass('hidden');
                    $('#last-certification-preview').addClass('hidden');
                    $('.dropdown').removeClass('disabled')
                  
                      // Handle success
                    $('#pendaftaran-mahasiswa').removeClass('loading');
                    $('#pendaftaran-mahasiswa input').removeAttr('readonly');
                    $('#pendaftaran-mahasiswa button').removeAttr('disabled');
                    $('.positive.message').show();
                  } else {
                    // Custom message for failed HTTP codes
                    if (response.status === 400) throw new Error(result.data?result.data : '400, Bad Request');
                    if (response.status === 404) throw new Error(result.data?result.data : '404, Not found');
                    if (response.status === 500) throw new Error(result.data?result.data : '500, internal server error');
                    throw new Error(response.status);
                  }
                } catch (error) {
                    console.log(error);
                    $('#pendaftaran-mahasiswa').removeClass('loading')
                    $('#pendaftaran-mahasiswa input').removeAttr('readonly', false);
                    $('#pendaftaran-mahasiswa button').removeAttr('disabled', false);
                    $('.dropdown').removeClass('disabled')
                    $('.error.message').html(error.message);
                    $('.error.message').show();
                }
            }
        });
    });
</script>