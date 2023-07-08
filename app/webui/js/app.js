var app = angular.module('han-cloud', ['ngResource', 'ui.router', 'ngAnimate', 'ngMessages', '720kb.tooltips', 'angulartics', 'angulartics.google.analytics']);

app.constant('config', {
    envName: 'han-cloud',
    apiUrl: '../api'
});

app.factory('Requests', function ($resource, config) {
    return $resource(config.apiUrl+"/requests");
});

app.factory('versionFactory', ['$http',function ($http) {
    var _version = null;

    $http.get("../api/version").then(function(d){
        _version = d.data;
    });

    return {
        version: function () {
            return _version ? _version.version : null;
        },
        env: function () {
            return _version ? _version.env : null;
        }
    };
}]);

app.config(function($stateProvider, $urlRouterProvider) {
  //

  //
  // Now set up the states
  $stateProvider
    .state('splash', {
      url: "/",
      templateUrl: "views/splash.html",
      controller: function($rootScope) {
        $rootScope.bk_colour = 'img-splash no-fade';
        $rootScope.btn_forward = 'start';
      }
    })
    .state('start', {
      url: "/welcome",
      templateUrl: "views/start.html",
      controller: function($rootScope) {
        $rootScope.bk_colour = 'corp-blue no-fade';
        $rootScope.btn_back = 'splash';
        $rootScope.btn_forward = 'details';
      }
    })
    .state('details', {
      url: "/your-details",
      templateUrl: "views/customer.html",
      controller: function($rootScope) {
        $rootScope.bk_colour = 'corp-purple';
        $rootScope.btn_back = 'start';
        $rootScope.btn_forward = 'device';
      }
    })
    .state('device', {
      url: "/your-device",
      templateUrl: "views/device.html",
      controller: function($rootScope) {
        $rootScope.bk_colour = 'corp-cblue';
        $rootScope.btn_back = 'details';
        $rootScope.btn_forward = 'legal';
      }
    })
    .state('legal', {
      url: "/legal",
      templateUrl: "views/legal.html",
      controller: function($rootScope) {
        $rootScope.bk_colour = 'colour-error';
        $rootScope.btn_back = 'device';
        $rootScope.btn_forward = 'complete';
      }
    })
    .state('complete', {
      url: "/complete",
      templateUrl: "views/complete.html",
      controller: function($rootScope) {
        $rootScope.bk_colour = 'corp-light-blue';
      }
    })
    .state('error', {
      url: "/error",
      templateUrl: "views/error.html",
      controller: function($rootScope) {
        $rootScope.bk_colour = 'colour-error';
      }
    })
    .state('email-verified', {
      url: "/email-verified",
      templateUrl: "views/email-verified.html",
      controller: function($rootScope) {
        $rootScope.bk_colour = 'corp-light-blue';
      }
    })
    .state('cts', {
        url : '/cts/:reference',
        controller: 'ctrlClickthrough',
        templateUrl: 'views/clickthrough.html',
        resolve: {
          click: function($stateParams, $http){
            return $http.get("../api/cts/"+$stateParams.reference);
          }
        }
    });

    $urlRouterProvider.otherwise("/");
});

app.controller('ctrlClickthrough', ['$scope', '$location', 'Requests','click', function($scope, $location, Requests, click) {
  // process the 'next' if we have one
  $location.url(click.data.href);
  //console.log(click);
}]);

app.controller('hanCtrl', ['$scope', '$location', '$rootScope', 'Requests', 'versionFactory', function($scope, $location, $rootScope, Requests, versionFactory) {

  // Build version
  $scope.env = function(){return versionFactory.env();};
  $scope.version = function(){return versionFactory.version();};

  // Registration form
  $scope.r = {};
  $scope.r.version = "";
  $scope.r.request = {
        "email"                 : "",
        "first_name"            : "",
        "last_name"             : "",
        "business_name"         : "",
        "phone_primary"         : "",
        "phone_secondary"       : "",
        "address_flat"          : "",
        "address_number"        : "",
        "address_street_name"   : "",
        "address_street_type"   : "",
        "address_city"          : "",
        "address_state"         : "VIC",
        "address_postcode"      : "",
        "nmi"                   : "",
        "meter_number"          : "",
        "han_mac"               : "",
        "han_install_code"      : "",
        "eula"                  : 0
      };

  // Submission of form
  $scope.submitReg = function(){

    // Change EULA from true/false to -1/1
    if($scope.r.request.eula === true) $scope.r.request.eula = 1;
    if($scope.r.request.eula === false) $scope.r.request.eula = -1;

    // Format MAC address to be in the form 1a2b3c4d5e6f
    $scope.r.request.han_mac = $scope.r.request.han_mac.replace(/[^0-9a-fA-F]/g, '').toLowerCase();

    // Format Install Code to be all lowercase with no whitespaces
    $scope.r.request.han_install_code = $scope.r.request.han_install_code.replace(/[^0-9a-fA-F]/g, '').toLowerCase();

    $scope.r.version = $scope.version();

    Requests.save($scope.r, function(d){
      if(d['$resolved'] == true){
        $location.url('/complete');
      }
    }, function(error) {
        $rootScope.err = error;
        $location.url('/error');
    });

  };

  // NMI Checksum
  $scope.ncsValid = -1;
  $scope.NMIChecksum = function(nmi) {
    var total_cs = 0;
    var checksum = nmi.substr(10,1);
    var nmi = nmi.substr(0,10).split("");
    nmi.reverse();
    //console.log(nmi);
    angular.forEach(nmi, function(val,key) {
      var curr = val.charCodeAt(0);
      if((key % 2) == 0) {
        curr = curr * 2;
      }
      var t = curr.toString();
      t.split("");
      angular.forEach(t, function(val,key) {
        total_cs = total_cs + parseInt(val);
      });

    });
    var final_cs = (Math.floor(total_cs / 10)+1)*10-total_cs;
    if(final_cs == 10) {
      final_cs == 0;
    }
    if(final_cs == parseInt(checksum)) {
      return 1;
    }
    else {
      return 0;
    }
  }
  $scope.$watch('r.request.nmi', function(e){
      if(e) {
        var nmi = e;
        if(nmi.length > 10) {
          $scope.ncsValid = $scope.NMIChecksum(nmi);
        }
        else {
          $scope.ncsValid = 0;
        }
      }
      else {
        $scope.ncsValid = -1;
      }
  });

  // 'Install Code' CRC check
  $scope.CRCCheck = function(icode) {

      // Functions required for CRC check
      var crcTable = [
          0x0000, 0x1021, 0x2042, 0x3063, 0x4084, 0x50A5, 0x60C6, 0x70E7, 0x8108, 0x9129, 0xA14A, 0xB16B, 0xC18C, 0xD1AD, 0xE1CE, 0xF1EF,
          0x1231, 0x0210, 0x3273, 0x2252, 0x52B5, 0x4294, 0x72F7, 0x62D6, 0x9339, 0x8318, 0xB37B, 0xA35A, 0xD3BD, 0xC39C, 0xF3FF, 0xE3DE,
          0x2462, 0x3443, 0x0420, 0x1401, 0x64E6, 0x74C7, 0x44A4, 0x5485, 0xA56A, 0xB54B, 0x8528, 0x9509, 0xE5EE, 0xF5CF, 0xC5AC, 0xD58D,
          0x3653, 0x2672, 0x1611, 0x0630, 0x76D7, 0x66F6, 0x5695, 0x46B4, 0xB75B, 0xA77A, 0x9719, 0x8738, 0xF7DF, 0xE7FE, 0xD79D, 0xC7BC,
          0x48C4, 0x58E5, 0x6886, 0x78A7, 0x0840, 0x1861, 0x2802, 0x3823, 0xC9CC, 0xD9ED, 0xE98E, 0xF9AF, 0x8948, 0x9969, 0xA90A, 0xB92B,
          0x5AF5, 0x4AD4, 0x7AB7, 0x6A96, 0x1A71, 0x0A50, 0x3A33, 0x2A12, 0xDBFD, 0xCBDC, 0xFBBF, 0xEB9E, 0x9B79, 0x8B58, 0xBB3B, 0xAB1A,
          0x6CA6, 0x7C87, 0x4CE4, 0x5CC5, 0x2C22, 0x3C03, 0x0C60, 0x1C41, 0xEDAE, 0xFD8F, 0xCDEC, 0xDDCD, 0xAD2A, 0xBD0B, 0x8D68, 0x9D49,
          0x7E97, 0x6EB6, 0x5ED5, 0x4EF4, 0x3E13, 0x2E32, 0x1E51, 0x0E70, 0xFF9F, 0xEFBE, 0xDFDD, 0xCFFC, 0xBF1B, 0xAF3A, 0x9F59, 0x8F78,
          0x9188, 0x81A9, 0xB1CA, 0xA1EB, 0xD10C, 0xC12D, 0xF14E, 0xE16F, 0x1080, 0x00A1, 0x30C2, 0x20E3, 0x5004, 0x4025, 0x7046, 0x6067,
          0x83B9, 0x9398, 0xA3FB, 0xB3DA, 0xC33D, 0xD31C, 0xE37F, 0xF35E, 0x02B1, 0x1290, 0x22F3, 0x32D2, 0x4235, 0x5214, 0x6277, 0x7256,
          0xB5EA, 0xA5CB, 0x95A8, 0x8589, 0xF56E, 0xE54F, 0xD52C, 0xC50D, 0x34E2, 0x24C3, 0x14A0, 0x0481, 0x7466, 0x6447, 0x5424, 0x4405,
          0xA7DB, 0xB7FA, 0x8799, 0x97B8, 0xE75F, 0xF77E, 0xC71D, 0xD73C, 0x26D3, 0x36F2, 0x0691, 0x16B0, 0x6657, 0x7676, 0x4615, 0x5634,
          0xD94C, 0xC96D, 0xF90E, 0xE92F, 0x99C8, 0x89E9, 0xB98A, 0xA9AB, 0x5844, 0x4865, 0x7806, 0x6827, 0x18C0, 0x08E1, 0x3882, 0x28A3,
          0xCB7D, 0xDB5C, 0xEB3F, 0xFB1E, 0x8BF9, 0x9BD8, 0xABBB, 0xBB9A, 0x4A75, 0x5A54, 0x6A37, 0x7A16, 0x0AF1, 0x1AD0, 0x2AB3, 0x3A92,
          0xFD2E, 0xED0F, 0xDD6C, 0xCD4D, 0xBDAA, 0xAD8B, 0x9DE8, 0x8DC9, 0x7C26, 0x6C07, 0x5C64, 0x4C45, 0x3CA2, 0x2C83, 0x1CE0, 0x0CC1,
          0xEF1F, 0xFF3E, 0xCF5D, 0xDF7C, 0xAF9B, 0xBFBA, 0x8FD9, 0x9FF8, 0x6E17, 0x7E36, 0x4E55, 0x5E74, 0x2E93, 0x3EB2, 0x0ED1, 0x1EF0
          ];

          function reflect (val, width) {
            var resByte = 0;
            for (var i = 0; i < width; i++) {
              if ((val & (1 << i)) != 0) {
                resByte |= (1 << ((width-1) - i));
              }
            }
          return resByte;
          }

          function crc_x25(s) {
            var crc = 0xFFFF;
            var j, i;
            for (i = 0; i < s.length; i++) {
              c = reflect(s[i], 8);
              if (c > 255) {
                throw new RangeError();
              }
              crc ^= (c << 8) ;
              crc = ((crc << 8) ^ crcTable[( crc >> 8 ) & 0xFF]) ;
            }
            crc = reflect(crc, 16)
            return ((crc ^ 0xFFFF ) & 0xFFFF) ;
          }

          function hexToBytes(hex) {
            for (var bytes = [], c = 0; c < hex.length; c += 2)
              bytes.push(parseInt(hex.substr(c, 2), 16));
              return bytes;
          }

          function checksumCalc(data) {
            byteArray = hexToBytes(data);
            crc = crc_x25(byteArray);
            return ((crc & 0xFF) << 8) | (crc >> 8)
          }


          var install_code = icode.replace(/\s/g, '');
              install_code = install_code.substr(0,install_code.length-4).toLowerCase();
          var install_crc = icode.substr(icode.length-4);
              install_crc = install_crc.substr(0, icode.length-4).toLowerCase();
          var checked_crc = checksumCalc(install_code).toString(16).toLowerCase();


          if(install_crc == checked_crc) {
            return 1;
          }
          else {
            return 0;
          }
  };

  // Watch the 'Install Code' input box and only run verification function if Install Code is 48.64,96 or 128 bit

  $scope.CRCValid = -1;
  $scope.$watch('r.request.han_install_code', function(e){
      if(e) {
        var ic = e.replace(/\s/g, '');
        if(ic.length > 0 && (ic.length == 16 || ic.length == 20 || ic.length == 28 || ic.length == 36)) {
          $scope.CRCValid = 1;
          //$scope.CRCValid = $scope.CRCCheck(ic);
        }
        else {
          $scope.CRCValid = 0;
        }
      }
      else {
        $scope.CRCValid = -1;
      }

  });

  // Regular expression patterns
  $scope.regex_MACAddress = /^(([0-9a-f]{2})[\s-:.]?){5}((([0-9a-f]{2})[\s-:.]?){2})?([0-9a-f]{2})$/i;
  $scope.regex_InstallCode = /^([0-9a-f]{4}[\s-:]?){3,8}([0-9a-f]{4})$/i;
  $scope.regex_textInput = /^[+a-z0-9\s\-()&]*$/i;
  $scope.regex_postcode = /^3[0-9]{3}$/;
  $scope.regex_phoneNumber = /^\({0,1}((0|\+?61)[ ]?(2|4|3|7|8)){0,1}\){0,1}(?:[ -]?[0-9]){7}[0-9]$/;
  $scope.regex_email = /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/;
  $scope.regex_nmi = /^((6305|6306)[ ]?[0-9 ]{6,7})$/;
  $scope.regex_MeterNumber = /^[4-5]{1}[0-9]{6}$/;

  // Tooltip templates
  $scope.tooltips =
  {
    'ContactDetails': "Name as provided on your electricity bill.",
    'Address': "Address as provided on your electricity bill.",
    'NMI':"Your National Meter Identifier (NMI) is provided on your electricity bill.",
    'Email':"Email address to be used for all correspondence in relation to this request. We will verify this email address before processing your request.",
    'Business': "Business name as displayed on yorur electricity bill",
    'MeterNumber': "This is the serial number of the smart meter at your premises and is printed on your energy bill (or on the front of your electricity meter).",
    'MACAddress': "The MAC address is a unique identifier for your device and is used in conjunction with the Install Code, these codes ensure that only you have authorised access to your electricity consumption via your device. You can generally find these codes printed on the device or on the documentation provided with your device. Keep in mind when locating your MAC Address that it can also be referred to as a EUI-64 or Device ID.",
    'InstallCode': "The installation code is the security code for your device, and is used, in conjunction with the MAC address to enable connection of your device to your meter.  You can generally find these codes printed on the device or on the documentation provided with your device. Keep in mind when locating your Install code that it can also be referred to as IN IC or Install Code."
  };

}]);
