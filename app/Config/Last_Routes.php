<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Training\DashboardController');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(false);

// ── PUBLIC ────────────────────────────────────────────────────────────────────
// Public certificate verification (QC- / PC- numbers — new system)
$routes->get('verify/(:segment)', 'Training\QuizCertificateController::verify/$1',
    ['as' => 'verify', 'namespace' => 'App\Controllers']);

// Old enrollment-level cert verification (CERT- numbers)
$routes->group('certificate', ['namespace' => 'App\Controllers\Training'], function ($routes) {
    $routes->get('verify/(:segment)', 'CertificateController::verify/$1',  ['as' => 'cert.verify']);
    $routes->get('verify/search',     'CertificateController::verifySearch',['as' => 'cert.verify.search']);
});

// ── AUTH ──────────────────────────────────────────────────────────────────────
$routes->group('auth', ['namespace' => 'App\Controllers\Auth'], function ($routes) {
    $routes->get('login',          'AuthController::login',        ['as' => 'auth.login']);
    $routes->post('login',         'AuthController::authenticate');
    $routes->post('authenticate',  'AuthController::authenticate'); // fallback for some server configs
    $routes->get('logout',         'AuthController::logout',       ['as' => 'auth.logout']);
});

// ── PROTECTED ─────────────────────────────────────────────────────────────────
$routes->group('', ['filter' => 'auth', 'namespace' => 'App\Controllers\Training'], function ($routes) {

    $routes->get('/',         'DashboardController::index', ['as' => 'dashboard']);
    $routes->get('dashboard', 'DashboardController::index');

    // Programs
    $routes->group('programs', [], function ($routes) {
        $routes->get('/',               'ProgramController::index',    ['as' => 'programs.index']);
        $routes->get('create',          'ProgramController::create',   ['as' => 'programs.create']);
        $routes->post('store',          'ProgramController::store',    ['as' => 'programs.store']);
        $routes->get('(:num)/edit',     'ProgramController::edit/$1',  ['as' => 'programs.edit']);
        $routes->post('(:num)/update',  'ProgramController::update/$1',['as' => 'programs.update']);
        $routes->post('(:num)/delete',  'ProgramController::delete/$1',['as' => 'programs.delete']);
        $routes->get('(:num)',          'ProgramController::show/$1',  ['as' => 'programs.show']);
    });

    // Days
    $routes->group('days', [], function ($routes) {
        $routes->get('(:num)', 'DayController::show/$1', ['as' => 'days.show']);
    });

    // Enrollments
    $routes->group('enrollments', [], function ($routes) {
        $routes->get('(:num)/manage',  'EnrollmentController::manage/$1',      ['as' => 'enrollments.manage']);
        $routes->post('(:num)/enroll', 'EnrollmentController::enroll/$1',      ['as' => 'enrollments.enroll']);
        $routes->post('self/(:num)',   'EnrollmentController::selfEnroll/$1',   ['as' => 'enrollments.self']);
        $routes->get('my',             'EnrollmentController::myEnrollments',   ['as' => 'enrollments.my']);
        $routes->get('certificates',   'EnrollmentController::myCertificates',  ['as' => 'enrollments.certs']);
    });

    // Progress
    $routes->group('progress', [], function ($routes) {
        $routes->get('(:num)',           'ProgressController::show/$1',  ['as' => 'progress.show']);
        $routes->post('module/complete', 'ProgressController::complete', ['as' => 'progress.complete']);
    });

    // Quiz
    $routes->group('quiz', [], function ($routes) {
        $routes->get('program/(:num)',   'QuizController::index/$1',      ['as' => 'quiz.index']);
        $routes->get('start/(:num)',     'QuizController::start/$1',      ['as' => 'quiz.start']);
        $routes->post('submit',          'QuizController::submit',        ['as' => 'quiz.submit']);
        $routes->get('result/(:num)',    'QuizController::result/$1',     ['as' => 'quiz.result']);
        $routes->get('(:num)/manage',    'QuizController::manage/$1',     ['as' => 'quiz.manage']);
        $routes->get('(:num)/create',    'QuizController::createForm/$1', ['as' => 'quiz.create']);
        $routes->post('(:num)/store',    'QuizController::store/$1',      ['as' => 'quiz.store']);
    });

    // Old-style enrollment certificate (view/print)
    $routes->group('certificate', [], function ($routes) {
        $routes->get('(:num)',       'CertificateController::view/$1',  ['as' => 'cert.view']);
        $routes->get('print/(:num)', 'CertificateController::print/$1', ['as' => 'cert.print']);
    });

    // New per-quiz certificates
    $routes->group('my-certificates', [], function ($routes) {
        $routes->get('',             'QuizCertificateController::index',    ['as' => 'qcert.index']);
        $routes->get('/',            'QuizCertificateController::index');
        $routes->get('(:num)',       'QuizCertificateController::view/$1',  ['as' => 'qcert.view']);
        $routes->get('print/(:num)', 'QuizCertificateController::print/$1', ['as' => 'qcert.print']);
    });

    // Agenda Upload
    $routes->group('agenda', [], function ($routes) {
        $routes->get('(:num)/upload',  'AgendaUploadController::form/$1',   ['as' => 'agenda.form']);
        $routes->post('(:num)/upload', 'AgendaUploadController::upload/$1', ['as' => 'agenda.upload']);
        $routes->get('template',       'AgendaUploadController::template',  ['as' => 'agenda.template']);
    });

    // Admin
    $routes->get('admin',     'AdminController::index', ['as' => 'admin.index']);
    $routes->get('fix-certs', 'FixCertController::run', ['as' => 'fix.certs']);

    // Trainees
    $routes->group('trainees', [], function ($routes) {
        $routes->get('/',             'TraineeController::index',   ['as' => 'trainees.index']);
        $routes->get('create',        'TraineeController::create',  ['as' => 'trainees.create']);
        $routes->post('store',        'TraineeController::store',   ['as' => 'trainees.store']);
        $routes->get('(:num)/edit',   'TraineeController::edit/$1', ['as' => 'trainees.edit']);
        $routes->post('(:num)/update','TraineeController::update/$1',['as' => 'trainees.update']);
        $routes->post('(:num)/toggle','TraineeController::toggle/$1',['as' => 'trainees.toggle']);
    });

    // Media
    $routes->group('media', [], function ($routes) {
        $routes->get('(:num)/manage',       'MediaController::manageForm/$1',    ['as' => 'media.manage']);
        $routes->post('(:num)/save',        'MediaController::saveMedia/$1',     ['as' => 'media.save']);
        $routes->post('(:num)/voice-note',  'MediaController::recordVoiceNote/$1');
        $routes->post('(:num)/delete',      'MediaController::deleteMedia/$1',   ['as' => 'media.delete']);
        $routes->get('(:num)/stream',       'MediaController::stream/$1',        ['as' => 'media.stream']);
        $routes->get('(:num)/player-modal', 'MediaController::playerModal/$1',   ['as' => 'media.player.modal']);
        $routes->get('(:num)/player-frame', 'MediaController::playerModal/$1');
        $routes->post('track-progress',     'MediaController::trackProgress',    ['as' => 'media.track']);
    });
});
