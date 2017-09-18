'use strict';

angular.module('continuousPipeRiver')
    .controller('ProjectRegistryCredentialsController', function($scope, $remoteResource, RegistryCredentialsRepository, user, project) {
        var controller = this;

        this.loadCredentials = function() {
            $remoteResource.load('credentials', RegistryCredentialsRepository.findAll()).then(function (credentials) {
                $scope.credentials = credentials;
            });
        };

        $scope.deleteCredentials = function(credentials) {
            swal({
                title: "Are you sure?",
                text: "You will not be able to cancel this action!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, delete it!"
            }).then(function() {
                RegistryCredentialsRepository.remove(credentials).then(function() {
                    swal("Deleted!", "Credentials successfully deleted.", "success");

                    controller.loadCredentials();
                }, function() {
                    swal("Error !", "An unknown error occured while deleting credentials", "error");
                });
            }).catch(swal.noop);
        };
        
        $scope.isAdmin = user.isAdmin(project);

        this.loadCredentials();
    });
