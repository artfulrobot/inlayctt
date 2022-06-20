(function(angular, $, _) {

  console.log("art2");
  angular.module('crmInlayctt').config(function($routeProvider) {
      $routeProvider.when('/inlays/ctt/:id', {
        controller: 'CrmInlaycttctt',
        controllerAs: '$ctrl',
        templateUrl: '~/crmInlayctt/ctt.html',

        // If you need to look up data when opening the page, list it out
        // under "resolve".
        resolve: {
          various: function($route, crmApi4) {
            const params = {
              inlayTypes: ['InlayType', 'get', {}, 'class'],
              tokens: ['OptionValue', 'get', {
                select: ["value", "label"],
                where: [["option_group_id:name", "=", "click_to_tweet_field"]],
                orderBy: {"option_group_id:label":"ASC"}
              }],
            };
            if ($route.current.params.id > 0) {
              params.inlay = ['Inlay', 'get', {where: [["id", "=", $route.current.params.id]]}, 0];
            }
            return crmApi4(params);
          }
        }
      });
    }
  );

  // The controller uses *injection*. This default injects a few things:
  //   $scope -- This is the set of variables shared between JS and HTML.
  //   crmApi, crmStatus, crmUiHelp -- These are services provided by civicrm-core.
  //   myContact -- The current contact, defined above in config().
  angular.module('crmInlayctt').controller('CrmInlaycttctt', function($scope, crmApi4, crmStatus, crmUiHelp, various) {
    // The ts() and hs() functions help load strings for this module.
    var ts = $scope.ts = CRM.ts('inlayctt');
    var hs = $scope.hs = crmUiHelp({file: 'CRM/crmInlayctt/ctt'}); // See: templates/CRM/crmInlayctt/ctt.hlp
    // Local variable for this controller (needed when inside a callback fn where `this` is not available).
    var ctrl = this;

    ctrl.inlayType = various.inlayTypes['Civi\\Inlay\\ClickToTweet'];

    if (various.inlay) {
      ctrl.inlay = various.inlay;
    }
    else {
      ctrl.inlay = {
        'class' : 'Civi\\Inlay\\ClickToTweet',
        name: 'New ' + ctrl.inlayType.name,
        public_id: 'new',
        id: 0,
        config: JSON.parse(JSON.stringify(ctrl.inlayType.defaultConfig)),
      };
    }

    ctrl.tokens = [
      {token: '{mpName}', description: 'MP name'},
      {token: '{mpTwitter}', description: 'MP’s twitter handle (with @)'},
      {token: '{mpConstituency}', description: 'MP’s constituency'},
    ];
    various.tokens.forEach(row => {
      ctrl.tokens.push({token: '{' + row.value + '}', description: row.label});
    });

    ctrl.save = function() {
      console.log("Saving", {inlay: ctrl.inlay});
      return crmStatus(
        // Status messages. For defaults, just use "{}"
        {start: ts('Saving...'), success: ts('Saved')},
        // The save action. Note that crmApi() returns a promise.
        crmApi4('Inlay', 'save', { records: [ctrl.inlay] })
      ).then(r => {
        console.log("save result", r);
        window.location = CRM.url('civicrm/a?#inlays');
      });
    };
  });

})(angular, CRM.$, CRM._);
