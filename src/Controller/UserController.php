<?php
namespace App\Controller;

use Cake\Event\Event;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use DateTime;

class UserController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Captcha');
        $this->loadComponent('API');
    }

    function getCaptcha()
    {
        $this->disableAutoRender();
        //generate random characters for captcha
        $random = mt_rand(100, 99999);
        //save characters in session
        $this->getRequest()->getSession()->write('captcha_code', $random);
        $settings = [
            'characters' => $random,
            'winHeight' => 50,         // captcha image height
            'winWidth' => 220,           // captcha image width
            'fontSize' => 25,          // captcha image characters fontsize
            'fontPath' => WWW_ROOT . 'tahomabd.ttf',    // captcha image font
            'noiseColor' => '#ccc',
            'bgColor' => '#fff',
            'noiseLevel' => '100',
            'textColor' => '#000'
        ];
        $img = $this->Captcha->ShowImage($settings);
        return $this->response->withBody($img);
    }

    function ajaxRegister()
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');
        if ($this->request->is('Post')) { // si la requête est bien un post
            $conditionsChecked = !empty($this->getRequest()->getData('condition')) || !$this->Configuration->getKey('condition');
            if (!empty($this->getRequest()->getData('pseudo')) && !empty($this->getRequest()->getData('password')) && $conditionsChecked && !empty($this->getRequest()->getData('password_confirmation') && !empty($this->getRequest()->getData('email')))) { // si tout les champs sont bien remplis
                //check uuid if needed
                $this->request = $this->getRequest()->withData('', $this->getRequest()->getData('xss'));
                if ($this->Configuration->getKey('check_uuid')) {
                    $pseudoToUUID = file_get_contents("https://api.mojang.com/users/profiles/minecraft/" . htmlentities($this->getRequest()->getData('pseudo')));
                    if (!$pseudoToUUID) {
                        return $this->response->withStringBody(json_encode([
                            'statut' => false,
                            'msg' => $this->Lang->get('USER__ERROR_UUID')
                        ]));
                    }

                    $this->request = $this->getRequest()->withData('uuid', json_decode($pseudoToUUID, true)['id']);
                }
                // Captcha
                if ($this->Configuration->getKey('captcha_type') == "2" || $this->Configuration->getKey('captcha_type') == "3") { // ReCaptcha and h-captcha
                    $validCaptcha = $this->Util->isValidReCaptcha($this->getRequest()->getData('recaptcha'), $this->Util->getIP(), $this->Configuration->getKey('captcha_secret'), $this->Configuration->getKey('captcha_type'));
                } else {
                    $captcha = $this->getRequest()->getSession()->read('captcha_code');
                    $validCaptcha = (!empty($captcha) && $captcha == $this->getRequest()->getData('captcha'));
                }
                //
                if ($validCaptcha) { // on check le captcha déjà
                    $isValid = $this->User->validRegister($this->getRequest()->getData(), $this->Util);
                    if ($isValid === true) { // on vérifie si y'a aucune erreur
                        $eventData = $this->getRequest()->getData();
                        $eventData['password'] = $this->Util->password($eventData['password'], $eventData['pseudo']);
                        $event = new Event('beforeRegister', $this, ['data' => $eventData]);
                        $this->getEventManager()->dispatch($event);
                        if ($event->isStopped()) {
                            return $event->getResult();
                        }
                        // we record
                        $this->request = $this->request->withData('microsoft_user_id', null);
                        $this->request = $this->request->withData('registered_by_microsoft', false);
                        $userSession = $this->User->register($this->getRequest()->getData(), $this->Util);
                        // We send the mail if in the configuration it is activated
                        if ($this->Configuration->getKey('confirm_mail_signup')) {
                            $confirmCode = substr(md5(uniqid()), 0, 12);
                            $emailMsg = $this->Lang->get('EMAIL__CONTENT_CONFIRM_MAIL', [
                                '{LINK}' => $this->Configuration->getKey('website_url') . "/user/confirm/$confirmCode",
                                '{IP}' => $this->Util->getIP(),
                                '{USERNAME}' => $this->getRequest()->getData('pseudo'),
                                '{DATE}' => $this->Lang->date(date('Y-m-d H:i:s'))
                            ]);
                            $email = $this->Util->prepareMail(
                                $this->getRequest()->getData('email'),
                                $this->Lang->get('EMAIL__TITLE_CONFIRM_MAIL'),
                                $emailMsg
                            )->sendMail();
                            if ($email) {
                                $user = $this->User->get($userSession);
                                $user->set(['confirmed' => $confirmCode]);
                                $this->User->save($user);
                            }
                        }
                        if (!$this->Configuration->getKey('confirm_mail_signup_block')) { // si on doit pas bloquer le compte si non confirmé
                            // on prépare la connexion
                            $this->getRequest()->getSession()->write('user', $userSession);
                            $event = new Event('onLogin', $this, ['user' => $this->User->getAllFromCurrentUser(), 'register' => true]);
                            $this->getEventManager()->dispatch($event);
                            if ($event->isStopped()) {
                                return $event->getResult();
                            }
                        }
                        // on dis que c'est bon
                        return $this->response->withStringBody(json_encode([
                            'statut' => true,
                            'msg' => $this->Lang->get('USER__REGISTER_SUCCESS')
                        ]));
                    } else { // si c'est pas bon, on envoie le message d'erreur retourné par l'étape de validation
                        return $this->response->withStringBody(json_encode([
                            'statut' => false,
                            'msg' => $this->Lang->get($isValid)
                        ]));
                    }
                } else {
                    return $this->response->withStringBody(json_encode([
                        'statut' => false,
                        'msg' => $this->Lang->get('FORM__INVALID_CAPTCHA')
                    ]));
                }
            } else {
                return $this->response->withStringBody(json_encode([
                    'statut' => false,
                    'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')
                ]));
            }
        } else {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => $this->Lang->get('ERROR__BAD_REQUEST')
            ]));
        }
    }

    function ajaxLogin()
    {
        if (!$this->request->is('post'))
            throw new BadRequestException();
        if (empty($this->getRequest()->getData('pseudo')) || empty($this->getRequest()->getData('password')))
            return $this->sendJSON(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]);
        $this->autoRender = false;
        $this->response->withType('json');
        $this->Authentification = TableRegistry::getTableLocator()->get('Authentification');
        $this->request = $this->request->withData('', $this->getRequest()->getData('xss'));
        $user_login = $this->User->getAllFromUser($this->getRequest()->getData('pseudo'));

        if (empty($user_login))
            return $this->sendJSON(['statut' => false, 'msg' => $this->Lang->get('USER__ERROR_INVALID_CREDENTIALS')]);

        $infos = $this->Authentification->find('all', conditions: ['user_id' => $user_login['id'], 'enabled' => true])->first();

        $confirmEmailIsNeeded = ($this->Configuration->getKey('confirm_mail_signup') && $this->Configuration->getKey('confirm_mail_signup_block'));
        $login = $this->User->login($user_login, $this->getRequest()->getData(), $confirmEmailIsNeeded, $this->Configuration->getKey('check_uuid'), $this);
        if (!isset($login['status']) || $login['status'] !== true) {
            return $this->sendJSON([
                'statut' => false,
                'msg' => $this->Lang->get($login, ['{URL_RESEND_EMAIL}' => Router::url(['action' => 'resend_confirmation'])])
            ]);
        }

        $event = new Event('onLogin', $this, ['user' => $user_login]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped())
            return $event->getResult();
        if ($infos) {
            $this->getRequest()->getSession()->write('user_id_two_factor_auth', $user_login['id']);
            return $this->sendJSON([
                'statut' => true,
                'msg' => $this->Lang->get('USER__REGISTER_LOGIN'),
                'two-factor-auth' => true
            ]);
        } else {
            if ($this->getRequest()->getData('remember_me')) {
                $cookie = new Cookie('remember_me', [
                    'pseudo' => $this->getRequest()->getData('pseudo'),
                    'password' => $this->User->getFromUser('password', $this->getRequest()->getData('pseudo'))
                ], new DateTime('+1 weeks'));
                $this->response = $this->getResponse()->withCookie($cookie);
            }
            $this->getRequest()->getSession()->write('user', $login['session']);
            return $this->sendJSON(['statut' => true, 'msg' => $this->Lang->get('USER__REGISTER_LOGIN')]);
        }
    }

    function confirm($code = false)
    {
        $this->autoRender = false;
        if (isset($code)) {
            $find = $this->User->find('all', ['conditions' => ['confirmed' => $code]])->first();
            if (!empty($find)) {
                $event = new Event('beforeConfirmAccount', $this, ['user_id' => $find['User']['id']]);
                $this->getEventManager()->dispatch($event);
                if ($event->isStopped()) {
                    return $event->getResult();
                }
                $user = $this->User->get($find['User']['id']);
                $user->set(['confirmed' => date('Y-m-d H:i:s')]);
                $this->User->save($user);
                $userSession = $find['User']['id'];
                $this->Notification = TableRegistry::getTableLocator()->get('Notification');
                $this->Notification->setToUser($this->Lang->get('USER__CONFIRM_NOTIFICATION'), $find['User']['id']);
                $this->getRequest()->getSession()->write('user', $userSession);
                $event = new Event('onLogin', $this, ['user' => $this->User->getAllFromCurrentUser(), 'confirmAccount' => true]);
                $this->getEventManager()->dispatch($event);
                if ($event->isStopped()) {
                    return $event->getResult();
                }
                return $this->redirect(['action' => 'profile']);
            } else {
                throw new NotFoundException();
            }
        } else {
            throw new NotFoundException();
        }
    }

    function ajax_lostpasswd()
    {
        $this->layout = null;
        $this->autoRender = false;
        $this->response->withType('json');
        if ($this->request->is('ajax')) {
            if (!empty($this->getRequest()->getData('email'))) {
                $this->User = TableRegistry::getTableLocator()->get('User');
                if (filter_var($this->getRequest()->getData('email'), FILTER_VALIDATE_EMAIL)) {
                    $search = $this->User->find('all', conditions: ['email' => $this->getRequest()->getData('email')])->first();
                    if (!empty($search)) {
                        if ($search['User']['registered_by_microsoft'])
                            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('USER__AUTH_MICROSOFT_CANNOT_RESET_PASSWORD')]));
                        $this->Lostpassword = TableRegistry::getTableLocator()->get('Lostpassword');
                        $key = substr(md5(rand() . date('sihYdm')), 0, 10);
                        $to = $this->getRequest()->getData('email');
                        $subject = $this->Lang->get('USER__PASSWORD_RESET_LINK');
                        $message = $this->Lang->get('USER__PASSWORD_RESET_EMAIL_CONTENT', [
                            '{EMAIL}' => $this->getRequest()->getData('email'),
                            '{PSEUDO}' => $search['User']['pseudo'],
                            '{LINK}' => $this->Configuration->getKey('website_url') . "/?resetpasswd_$key"
                        ]);
                        $event = new Event('beforeSendResetPassMail', $this, ['user_id' => $search['User']['id'], 'key' => $key]);
                        $this->getEventManager()->dispatch($event);
                        if ($event->isStopped()) {
                            return $event->getResult();
                        }
                        if ($this->Util->prepareMail($to, $subject, $message)->sendMail()) {
                            $lostPass = $this->Lostpassword->newEntity([
                                'email' => $this->getRequest()->getData('email'),
                                'key' => $key
                            ]);
                            $this->Lostpassword->save($lostPass);
                            $this->response->withStringBody(json_encode([
                                'statut' => true,
                                'msg' => $this->Lang->get('USER__PASSWORD_FORGOT_EMAIL_SUCCESS')
                            ]));
                        } else {
                            $this->response->withStringBody(json_encode([
                                'statut' => false,
                                'msg' => $this->Lang->get('ERROR__INTERNAL_ERROR')
                            ]));
                        }
                    } else {
                        $this->response->withStringBody(json_encode([
                            'statut' => false,
                            'msg' => $this->Lang->get('USER__ERROR_NOT_FOUND')
                        ]));
                    }
                } else {
                    $this->response->withStringBody(json_encode([
                        'statut' => false,
                        'msg' => $this->Lang->get('USER__ERROR_EMAIL_NOT_VALID')
                    ]));
                }
            } else {
                $this->response->withStringBody(json_encode([
                    'statut' => false,
                    'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')
                ]));
            }
        } else {
            $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => $this->Lang->get('ERROR__BAD_REQUEST')
            ]));
        }

        return null;
    }

    function ajax_resetpasswd()
    {
        $this->autoRender = false;
        $this->response->withType('json');
        if ($this->request->is('ajax')) {
            if (!empty($this->getRequest()->getData('password')) and !empty($this->getRequest()->getData('password2')) and !empty($this->getRequest()->getData('email')) && !empty($this->getRequest()->getData('key'))) {
                $this->request = $this->getRequest()->getData('xss');
                $reset = $this->User->resetPass($this->request->getData(), $this);
                if (isset($reset['status']) && $reset['status'] === true) {
                    $this->getRequest()->getSession()->write('user', $reset['session']);
                    $this->History->set('RESET_PASSWORD', 'user');
                    $this->response->withStringBody(json_encode([
                        'statut' => true,
                        'msg' => $this->Lang->get('USER__PASSWORD_RESET_SUCCESS')
                    ]));
                } else {
                    $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get($reset)]));
                }
            } else {
                $this->response->withStringBody(json_encode([
                    'statut' => false,
                    'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')
                ]));
            }
        } else {
            $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => $this->Lang->get('ERROR__BAD_REQUEST')
            ]));
        }
    }

    function logout()
    {
        $this->autoRender = false;
        $event = new Event('onLogout', $this, ['session' => $this->getRequest()->getSession()->read('user')]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            return $event->getResult();
        }

        if ($this->getRequest()->getCookie('microsoft_user_id')) {
            $this->getRequest()->getCookieCollection()->remove('microsoft_user_id');
        }

        if ($this->getRequest()->getCookie('remember_me')) {
            $this->getRequest()->getCookieCollection()->remove('remember_me');
        }
        $this->getRequest()->getSession()->delete('user');
        return $this->redirect($this->referer());
    }

    function uploadSkin()
    {
        $this->autoRender = false;
        $this->response->withType('json');
        if ($this->isConnected && $this->API->can_skin()) {
            if ($this->request->is('post')) {
                $username = $this->User->getKey('pseudo');
                $this->ApiConfiguration = TableRegistry::getTableLocator()->get('ApiConfiguration');
                $ApiConfiguration = $this->ApiConfiguration->find()->first();

                $useSkinRestorer = $ApiConfiguration['use_skin_restorer'];
                $serverSkinRestorerID = $ApiConfiguration['skin_restorer_server_id'];

                if ($useSkinRestorer & !$this->Server->userIsConnected($username, $serverSkinRestorerID)) {
                    $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('API__SKIN_RESTORER_NOT_CONNECTED')]));
                    return;
                }
                $skin_max_size = 10000000; // octet

                $target_config = $ApiConfiguration['skin_filename'];
                $filename = substr($target_config, (strrpos($target_config, '/') + 1));
                $filename = str_replace('{PLAYER}', $username, $filename);
                $filename = str_replace('php', '', $filename);
                $filename = str_replace('.', '', $filename);
                $filename = $filename . '.png';
                $target = substr($target_config, 0, (strrpos($target_config, '/') + 1));
                $target = WWW_ROOT . '/' . $target;
                $width_max = $ApiConfiguration['skin_width']; // pixel
                $height_max = $ApiConfiguration['skin_height']; // pixel
                $isValidImg = $this->Util->isValidImage($this->request, ['png'], $width_max, $height_max, $skin_max_size);
                if (!$isValidImg['status']) {
                    $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $isValidImg['msg']]));
                    return;
                }
                if (!$this->Util->uploadImage($this->request, $target . $filename)) {
                    $this->response->withStringBody(json_encode([
                        'statut' => false,
                        'msg' => $this->Lang->get('FORM__ERROR_WHEN_UPLOAD')
                    ]));
                    return;
                }

                $skinURL = Router::url(['action' => str_replace("{PLAYER}", $username, $ApiConfiguration['ApiConfiguration']['skin_filename']) . ".png", 'controller' => '', 'admin' => false], true);

                $skinRestorerCommand = str_replace(['{PLAYER}', '{URL}'], [$username, $skinURL], "skin set {PLAYER} {URL}");
                $this->Server->commands($skinRestorerCommand, $serverSkinRestorerID);

                $this->response->withStringBody(json_encode([
                    'statut' => true,
                    'msg' => $this->Lang->get('API__UPLOAD_SKIN_SUCCESS')
                ]));
            }
        } else {
            throw new ForbiddenException();
        }
    }

    function uploadCape()
    {
        $this->autoRender = false;
        $this->response->withType('json');
        if ($this->isConnected && $this->API->can_cape()) {
            if ($this->request->is('post')) {
                $cape_max_size = 10000000; // octet
                $this->ApiConfiguration = TableRegistry::getTableLocator()->get('ApiConfiguration');
                $ApiConfiguration = $this->ApiConfiguration->find()->first();
                $target_config = $ApiConfiguration['cape_filename'];
                $filename = substr($target_config, (strrpos($target_config, '/') + 1));
                $filename = str_replace('{PLAYER}', $this->User->getKey('pseudo'), $filename);
                $filename = str_replace('php', '', $filename);
                $filename = str_replace('.', '', $filename);
                $filename = $filename . '.png';
                $target = substr($target_config, 0, (strrpos($target_config, '/') + 1));
                $target = WWW_ROOT . '/' . $target;
                $width_max = $ApiConfiguration['cape_width']; // pixel
                $height_max = $ApiConfiguration['cape_height']; // pixel
                $isValidImg = $this->Util->isValidImage($this->request, ['png'], $width_max, $height_max, $cape_max_size);
                if (!$isValidImg['status']) {
                    $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $isValidImg['msg']]));
                    return;
                }
                if (!$this->Util->uploadImage($this->request, $target . $filename)) {
                    $this->response->withStringBody(json_encode([
                        'statut' => false,
                        'msg' => $this->Lang->get('FORM__ERROR_WHEN_UPLOAD')
                    ]));
                    return;
                }
                $this->response->withStringBody(json_encode([
                    'statut' => true,
                    'msg' => $this->Lang->get('API__UPLOAD_CAPE_SUCCESS')
                ]));
            }
        } else {
            throw new ForbiddenException();
        }
    }

    function profile()
    {
        if ($this->isConnected) {
            // Check if user has twofactorauth enabled
            $this->Authentification = TableRegistry::getTableLocator()->get('Authentification');
            $infos = $this->Authentification->find('all', conditions: ['user_id' => $this->User->getKey('id'), 'enabled' => true])->first();
            if (empty($infos)) // no two factor auth
                $this->set('twoFactorAuthStatus', false);
            else
                $this->set('twoFactorAuthStatus', true);
            $this->set('title_for_layout', $this->User->getKey('pseudo'));
            $this->layout = $this->Configuration->getKey('layout');
            if ($this->EyPlugin->isInstalled('eywek.shop')) {
                $this->ItemsBuyHistory = TableRegistry::getTableLocator()->get('Shop.ItemsBuyHistory');
                $histories = $this->ItemsBuyHistory->find('all',
                recursive: 1,
                order: 'ItemsBuyHistory.created DESC',
                conditions: ['user_id' => $this->User->getKey('id')])->all();
                $this->set(compact('histories'));
                $this->set('shop_active', true);
            } else {
                $this->set('shop_active', false);
            }
            $available_ranks = [
                0 => $this->Lang->get('USER__RANK_MEMBER'),
                2 => $this->Lang->get('USER__RANK_MODERATOR'),
                3 => $this->Lang->get('USER__RANK_ADMINISTRATOR'),
                4 => $this->Lang->get('USER__RANK_ADMINISTRATOR')
            ];
            $this->Rank = TableRegistry::getTableLocator()->get('Rank');
            $custom_ranks = $this->Rank->find()->all();
            foreach ($custom_ranks as $value) {
                $available_ranks[$value['rank_id']] = $value['name'];
            }
            $this->set(compact('available_ranks'));
            $this->set('can_cape', $this->API->can_cape());
            $this->set('can_skin', $this->API->can_skin());
            $this->ApiConfiguration = TableRegistry::getTableLocator()->get('ApiConfiguration');
            $configAPI = $this->ApiConfiguration->find()->first();
            $skin_width_max = $configAPI['skin_width'];
            $skin_height_max = $configAPI['skin_height'];
            $cape_width_max = $configAPI['cape_width'];
            $cape_height_max = $configAPI['cape_height'];
            $this->set(compact('skin_width_max', 'skin_height_max', 'cape_width_max', 'cape_height_max'));
            $confirmed = $this->User->getKey('confirmed');
            if ($this->Configuration->getKey('confirm_mail_signup') && !empty($confirmed) && date('Y-m-d H:i:s', strtotime($confirmed)) != $confirmed) { // si ca ne correspond pas à une date -> compte non confirmé
                $this->Flash->warning($this->Lang->get('USER__MSG_NOT_CONFIRMED_EMAIL', ['{URL_RESEND_EMAIL}' => Router::url(['action' => 'resend_confirmation'])]));
            }
            $connected_by_microsoft = false;
            $microsoft_user_id = $this->getRequest()->getCookie('microsoft_user_id');
            if (isset($microsoft_user_id))
                $connected_by_microsoft = true;
            $this->set(compact('connected_by_microsoft'));
        } else {
            $this->redirect('/');
        }
    }

    function resend_confirmation()
    {
        if (!$this->isConnected && !$this->getRequest()->getSession()->check('email.confirm.user.id'))
            throw new ForbiddenException();
        if ($this->isConnected)
            $user = $this->User->getAllFromCurrentUser();
        else
            $user = $this->User->find('all', ['conditions' => ['id' => $this->getRequest()->getSession()->read('email.confirm.user.id')]])->first();
        $this->getRequest()->getSession()->delete('email.confirm.user.id');
        if (!$user || empty($user))
            throw new NotFoundException();
        if (isset($user['User']))
            $user = $user['User'];
        $confirmed = $user['confirmed'];
        if (!$this->Configuration->getKey('confirm_mail_signup') || empty($confirmed) || date('Y-m-d H:i:s', strtotime($confirmed)) == $confirmed)
            throw new NotFoundException();
        $emailMsg = $this->Lang->get('EMAIL__CONTENT_CONFIRM_MAIL', [
            '{LINK}' => $this->Configuration->getKey('website_url') . "/user/confirm/$confirmed",
            '{IP}' => $this->Util->getIP(),
            '{USERNAME}' => $user['pseudo'],
            '{DATE}' => $this->Lang->date(date('Y-m-d H:i:s'))
        ]);
        $email = $this->Util->prepareMail(
            $user['email'],
            $this->Lang->get('EMAIL__TITLE_CONFIRM_MAIL'),
            $emailMsg
        )->sendMail();
        if ($email)
            $this->Flash->success($this->Lang->get('USER__CONFIRM_EMAIL_RESEND_SUCCESS'));
        else
            $this->Flash->error($this->Lang->get('USER__CONFIRM_EMAIL_RESEND_FAIL'));
        if ($this->isConnected)
            $this->redirect(['action' => 'profile']);
        else
            $this->redirect('/');
    }

    function changePw()
    {
        $this->autoRender = false;
        $this->response->withType('application/json');
        if ($this->isConnected) {
            if ($this->request->is('ajax')) {
                if (!empty($this->getRequest()->getData('password')) and !empty($this->getRequest()->getData('password_confirmation'))) {
                    $this->request = $this->request->withData('', $this->getRequest()->getData('xss'));
                    $password = $this->Util->password($this->getRequest()->getData('password'), $this->User->getKey('pseudo'));
                    $password_confirmation = $this->Util->password($this->getRequest()->getData('password_confirmation'), $this->User->getKey('pseudo'), $password);
                    if ($password == $password_confirmation) {
                        $event = new Event('beforeUpdatePassword', $this, ['user' => $this->User->getAllFromCurrentUser(), 'new_password' => $password]);
                        $this->getEventManager()->dispatch($event);
                        if ($event->isStopped()) {
                            return $event->getResult();
                        }
                        $this->User->setKey('password', $password);
                        $this->User->setKey('password_hash', $this->Util->getPasswordHashType());
                        return $this->response->withStringBody(json_encode([
                            'statut' => true,
                            'msg' => $this->Lang->get('USER__PASSWORD_UPDATE_SUCCESS')
                        ]));
                    } else {
                        return $this->response->withStringBody(json_encode([
                            'statut' => false,
                            'msg' => $this->Lang->get('USER__ERROR_PASSWORDS_NOT_SAME')
                        ]));
                    }
                } else {
                    return $this->response->withStringBody(json_encode([
                        'statut' => false,
                        'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')
                    ]));
                }
            } else {
                return $this->response->withStringBody(json_encode([
                    'statut' => false,
                    'msg' => $this->Lang->get('ERROR__BAD_REQUEST')
                ]));
            }
        } else {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => $this->Lang->get('USER__ERROR_MUST_BE_LOGGED')
            ]));
        }
    }

    function changeEmail()
    {
        $this->autoRender = false;
        $this->response->withType('application/json');
        if ($this->isConnected && $this->Permissions->can('EDIT_HIS_EMAIL')) {
            if ($this->request->is('ajax')) {
                if (!empty($this->getRequest()->getData('email')) and !empty($this->getRequest()->getData('email_confirmation'))) {
                    if ($this->getRequest()->getData('email') == $this->getRequest()->getData('email_confirmation')) {
                        if (filter_var($this->getRequest()->getData('email'), FILTER_VALIDATE_EMAIL)) {
                            $event = new Event('beforeUpdateEmail', $this, [
                                'user' => $this->User->getAllFromCurrentUser(),
                                'new_email' => $this->getRequest()->getData('email_confirmation')
                            ]);
                            $this->getEventManager()->dispatch($event);
                            if ($event->isStopped()) {
                                return $event->getResult();
                            }
                            $this->User->setKey('email', htmlentities($this->getRequest()->getData('email')));
                            return $this->response->withStringBody(json_encode([
                                'statut' => true,
                                'msg' => $this->Lang->get('USER__EMAIL_UPDATE_SUCCESS')
                            ]));
                        } else {
                            return $this->response->withStringBody(json_encode([
                                'statut' => false,
                                'msg' => $this->Lang->get('USER__ERROR_EMAIL_NOT_VALID')
                            ]));
                        }
                    } else {
                        return $this->response->withStringBody(json_encode([
                            'statut' => false,
                            'msg' => $this->Lang->get('USER__ERROR_EMAIL_NOT_SAME')
                        ]));
                    }
                } else {
                    return $this->response->withStringBody(json_encode([
                        'statut' => false,
                        'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')
                    ]));
                }
            } else {
                return $this->response->withStringBody(json_encode([
                    'statut' => false,
                    'msg' => $this->Lang->get('ERROR__BAD_REQUEST')
                ]));
            }
        } else {
            throw new ForbiddenException();
        }
    }
}
