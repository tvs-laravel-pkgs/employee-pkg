app.component('attendanceCheckOutMethodList', {
    templateUrl: attendance_check_out_method_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#search_punch_out_method').focus();
        var self = this;
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('punch-out-methods')) {
            window.location = "#!/permission-denied";
            return false;
        }
        self.add_permission = self.hasPermission('add-punch-out-method');
        var table_scroll;
        table_scroll = $('.page-main-content.list-page-content').height() - 37;
        var dataTable = $('#punch_out_methods_list').DataTable({
            "dom": cndn_dom_structure,
            "language": {
                // "search": "",
                // "searchPlaceholder": "Search",
                "lengthMenu": "Rows _MENU_",
                "paginate": {
                    "next": '<i class="icon ion-ios-arrow-forward"></i>',
                    "previous": '<i class="icon ion-ios-arrow-back"></i>'
                },
            },
            pageLength: 10,
            processing: true,
            stateSaveCallback: function(settings, data) {
                localStorage.setItem('CDataTables_' + settings.sInstance, JSON.stringify(data));
            },
            stateLoadCallback: function(settings) {
                var state_save_val = JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
                if (state_save_val) {
                    $('#search_punch_out_method').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            scrollY: table_scroll + "px",
            scrollCollapse: true,
            ajax: {
                url: laravel_routes['getPunchOutMethodList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.name = $("#name").val();
                    d.status = $("#status").val();
                },
            },

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'name', name: 'punch_out_methods.name' },
                { data: 'status', name: '' },

            ],
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_infos').html(total)
                $('.foot_info').html('Showing ' + start + ' to ' + end + ' of ' + max + ' entries')
            },
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        $scope.clear_search = function() {
            $('#search_punch_out_method').val('');
            $('#punch_out_methods_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#punch_out_methods_list').DataTable().ajax.reload();
        });

        var dataTables = $('#punch_out_methods_list').dataTable();
        $("#search_punch_out_method").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        //DELETE
        $scope.deletePunchOutMethod = function($id) {
            $('#punch_out_method_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#punch_out_method_id').val();
            $http.get(
                laravel_routes['deletePunchOutMethod'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Attendance Check Out Method Deleted Successfully');
                    $('#punch_out_methods_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/employee-pkg/attendance-check-out-method/list');
                }
            });
        }

        // FOR FILTER
        $http.get(
            laravel_routes['getPunchOutMethodFilter']
        ).then(function(response) {
            // console.log(response);
            self.extras = response.data.extras;
        });
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        $scope.clearSearchTerm = function() {
            $scope.searchTerm = '';
            $scope.searchTerm1 = '';
            $scope.searchTerm2 = '';
            $scope.searchTerm3 = '';
        };
        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });
        $scope.applyFilter = function() {
            $('#status').val(self.status);
            dataTables.fnFilter();
            $('#punch-out-method-filter-modal').modal('hide');
        }
        $scope.reset_filter = function() {
            $("#name").val('');
            $("#status").val('');
            dataTables.fnFilter();
            $('#punch-out-method-filter-modal').modal('hide');
        }
        $rootScope.loading = false;
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('attendanceCheckOutMethodForm', {
    templateUrl: attendance_check_out_method_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        var self = this;
        $("input:text:visible:first").focus();

        // alert(1);
        self.hasPermission = HelperService.hasPermission;
        // console.log(self.hasPermission('add-punch-out-method'));
        // return;
        if (!self.hasPermission('add-punch-out-method') && !self.hasPermission('edit-punch-out-method')) {
            window.location = "#!/permission-denied";
            return false;
        }
        // alert(1);
        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getPunchOutMethodFormData'], {
                params: {
                    id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
                }
            }
        ).then(function(response) {
            self.punch_out_method = response.data.punch_out_method;
            self.action = response.data.action;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                if (self.punch_out_method.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
            } else {
                self.switch_value = 'Active';
            }
        });

        //Save Form Data 
        var form_id = '#punch_out_method_form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'name': {
                    required: true,
                    minlength: 3,
                    maxlength: 64,
                }
            },
            messages: {
                'name': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 64 Characters',
                }
            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Please check the tab');
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: laravel_routes['savePunchOutMethod'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message);
                            $location.path('/employee-pkg/attendance-check-out-method/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('.submit').button('reset');
                                showErrorNoty(res);
                            } else {
                                $('.submit').button('reset');
                                $location.path('/employee-pkg/attendance-check-out-method/list');
                                $scope.$apply();
                            }
                        }
                    })
                    .fail(function(xhr) {
                        $('.submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        });
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------