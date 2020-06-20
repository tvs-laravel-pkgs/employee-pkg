app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.
    //EMPLOYEE
    when('/employee-pkg/employee/list', {
        template: '<employee-list></employee-list>',
        title: 'Employees',
    }).
    when('/employee-pkg/employee/add', {
        template: '<employee-form></employee-form>',
        title: 'Add Employee',
    }).
    when('/employee-pkg/employee/edit/:id', {
        template: '<employee-form></employee-form>',
        title: 'Edit Employee',
    }).
    when('/employee-pkg/employee/card-list', {
        template: '<employee-card-list></employee-card-list>',
        title: 'Employees Card List',
    }).

    //DESIGNATION
    when('/employee-pkg/designation/list', {
        template: '<designation-list></designation-list>',
        title: 'Designations',
    }).
    when('/employee-pkg/designation/add', {
        template: '<designation-form></designation-form>',
        title: 'Add Designation',
    }).
    when('/employee-pkg/designation/edit/:id', {
        template: '<designation-form></designation-form>',
        title: 'Edit Designation',
    }).
    when('/employee-pkg/designation/card-view', {
        template: '<designation-card-view></designation-card-view>',
        title: 'Designations Card View',
    }).

    //SKILL LEVELS
    when('/employee-pkg/skill-level/list', {
        template: '<skill-level-list></skill-level-list>',
        title: 'Skill Levels',
    }).
    when('/employee-pkg/skill-level/add', {
        template: '<skill-level-form></skill-level-form>',
        title: 'Add Skill Level',
    }).
    when('/employee-pkg/skill-level/edit/:id', {
        template: '<skill-level-form></skill-level-form>',
        title: 'Edit Skill Level',
    }).

    //ATTENDANCE CHECK OUT METHODS
    when('/employee-pkg/attendance-check-out-method/list', {
        template: '<attendance-check-out-method-list></attendance-check-out-method-list>',
        title: 'Attendance Check Out Methods',
    }).
    when('/employee-pkg/attendance-check-out-method/add', {
        template: '<attendance-check-out-method-form></attendance-check-out-method-form>',
        title: 'Add Attendance Check Out Method',
    }).
    when('/employee-pkg/attendance-check-out-method/edit/:id', {
        template: '<attendance-check-out-method-form></attendance-check-out-method-form>',
        title: 'Edit Attendance Check Out Method',
    });
}]);