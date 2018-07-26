<?php
// src/Controller/ApiController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Service\PaginationGenerator;

class ApiController extends AbstractController {

    private $_email = 'testeteamsysmailchimp@gmail.com';
    private $_listID = '77964287b8';
    private $_apiKey = 'cd33dfff8ec29f6aac2f8c4033df8c51-us18';
    private $_dataCenter = '';
    private $_url = '';

    public function __construct() {
        $this->_dataCenter = explode('-', $this->_apiKey)[1];
        $this->_url = 'https://' . $this->_dataCenter . '.api.mailchimp.com/3.0/';
    }

    /**
     * Récupère et affiche l'ensemble des utilisateurs de notre liste MailChimp (l'ensemble des résultats seront paginé)
     *
     * @param int $page => numéro de la page courante.
     *
     * @return Render Le code html contenant l'ensemble des utilisateurs
     */
    public function showUsers(int $page) {

        $target = 'lists/' . $this->_listID . '/members';
        $count = 25;
        $htmlPagination  = '';
        $users['members'] = array();
        
        $pagination = new PaginationGenerator();

        $total = $this->executeCurlRequest($target . '?fields=total_items', 'GET');

        if(!empty($total['type'])):
            $this->addFlash(
                'htmlResponse',
                $this->renderView('errors/mailChimpErrorsView.html.twig', array('error' => $total))
            );
        else:
            $url =  $this->generateUrl('show_users');

            $htmlPagination = $pagination->getPagination($total['total_items'], $count, $page, $url);
        endif;

        $users = $this->executeCurlRequest($target . '?count=' . $count . '&offset=' . ($count * ($page - 1)) , 'GET');

        if(!empty($ApiController['type'])):
            $this->addFlash(
                'htmlResponse',
                $this->renderView('errors/mailChimpErrorsView.html.twig', array('error' => $users))
            );
            $users['members'] = array();
        endif;
        
        return $this->render('home/homeView.html.twig', array(
            'users' => $users['members'],
            'pagination' => $htmlPagination
        ));
    }

    /**
     * Affiche le formulaire de création d'un nouvel utilisateur et l'enregistre dans notre liste d'utilisateur MailChimp
     *
     * @return render Le code html contenant le formulaire de création d'un nouvel utilisateur
     */
    public function create() {

        if(!empty($_REQUEST)):

            if(!empty($_REQUEST['EMAIL'])
            && !empty($_REQUEST['FNAME'])
            && !empty($_REQUEST['LNAME'])):

                $target = 'lists/' . $this->_listID . '/members';
                $data = json_encode(array(
                    'email_address' => $_REQUEST['EMAIL'],
                    'merge_fields' => array(
                        'FNAME' => $_REQUEST['FNAME'],
                        'LNAME' => $_REQUEST['LNAME']
                    ),
                    'status' => 'subscribed'
                ));

                $add = $this->executeCurlRequest($target, 'POST', $data);

                $this->addFlash(
                    'htmlResponse',
                    (!empty($add['type']))
                    ? $this->renderView('errors/mailChimpErrorsView.html.twig', array('error' => $add))
                    : $this->renderView('alerts/alertView.html.twig', array(
                        'type' => 'success',
                        'title' => 'User creating',
                        'text' => 'The new user has successfully created'))
                );
                
                return $this->redirectToRoute('create_user');
            else:
                $this->addFlash(
                    'htmlResponse',
                    $this->renderView('alerts/alertView.html.twig', array(
                        'type' => 'warning',
                        'title' => 'Warning',
                        'text' => 'All fields are required !'))
                );

                return $this->redirectToRoute('create_user');
            endif;
        endif;

        return $this->render('users/createView.html.twig');
    }

    /**
     * Affiche le formulaire de modification d'un utilisateur et l'enregistre les modifications y étant apporté
     *
     * @return render Le code html contenant le formulaire de modification d'un utilisateur
     */
    public function update(string $slug) {

        $user = $this->executeCurlRequest('lists/' . $this->_listID . '/members/' . $slug, 'GET');

        if(!empty($user['type'])):
            $this->addFlash(
                'htmlResponse',
                $this->renderView('errors/mailChimpErrorsView.html.twig', array('error' => $user))
            );
            $user = null;
        else:
            if(!empty($_REQUEST)):

                if(!empty($_REQUEST['EMAIL'])
                && !empty($_REQUEST['FNAME'])
                && !empty($_REQUEST['LNAME'])):

                    $data = json_encode(array(
                        'email_address' => $_REQUEST['EMAIL'],
                        'merge_fields' => array(
                            'FNAME' => $_REQUEST['FNAME'],
                            'LNAME' => $_REQUEST['LNAME']
                        ),
                        'status' => 'subscribed'
                    ));

                    $md5Email = md5($_REQUEST['EMAIL']);                

                    if($md5Email != $slug):

                        $exist = $this->executeCurlRequest('lists/' . $this->_listID . '/members/' . $md5Email, 'GET');
                        
                        if(empty($exist['type'])):
                            $this->addFlash(
                                'htmlResponse',
                                $this->renderView('alerts/alertView.html.twig', array(
                                    'type' => 'warning',
                                    'title' => 'Warning',
                                    'text' => $_REQUEST['EMAIL'] . ' is already used !'))
                            );
                            
                            return $this->redirectToRoute('update_user', array('slug' => $slug));
                        endif;
                    endif;

                    $update = $this->executeCurlRequest('lists/' . $this->_listID . '/members/' . $slug, 'PATCH', $data);

                    if(!empty($update['type'])):
                        $htmlResponse = $this->renderView('errors/mailChimpErrorsView.html.twig', array('error' => $update));
                        $updatedSlug = $slug;
                    else:
                        $htmlResponse = $this->renderView('alerts/alertView.html.twig', array(
                            'type' => 'success',
                            'title' => 'User updated',
                            'text' => 'The new user has successfully updated'));
                        $updatedSlug = $update['id'];
                    endif;

                    $this->addFlash(
                        'htmlResponse',
                        $htmlResponse
                    );
                    
                    return $this->redirectToRoute('update_user', array('slug' => $updatedSlug));

                else:
                    $this->addFlash(
                        'htmlResponse',
                        $this->renderView('alerts/alertView.html.twig', array(
                            'type' => 'warning',
                            'title' => 'Warning',
                            'text' => 'All fields are required !'))
                    );

                    return $this->redirectToRoute('update_user', array('slug' => $slug));
                endif;
            endif;
        endif;

        return $this->render('users/updateView.html.twig', array('user' => $user));
    }

    /**
     * Affiche le formulaire de modification d'un utilisateur et l'enregistre les modifications y étant apporté
     *
     * @return redirectToRoute Redirige vers la page contenant le listing des utilisateurs
     */
    public function delete(string $slug) {

        $delete = $this->executeCurlRequest('lists/' . $this->_listID . '/members/' . $slug, 'DELETE');

        $this->addFlash(
            'htmlResponse',
            (!empty($delete['type']))
            ? $this->renderView('errors/mailChimpErrorsView.html.twig', array('error' => $delete))
            : $this->renderView('alerts/alertView.html.twig', array(
                'type' => 'success',
                'title' => 'User deleted',
                'text' => 'The user has successfully deleted'))
        );

        return $this->redirectToRoute('show_users');
    }

    /**
     * Prépare et exécute la requête Curl
     * 
     * @param string $target => Seconde partie de l'url utilisée pour la requête; $type => Type de requête HTTP effectuée (GET, POST, PATCH, PUL, DELTE);
     *      $postfields => Contient l'ensemble des données spécifique devant-être envoyée au serveur MailChimp
     * @return array Le tableau contenant le résultat de la requête effectuée auprès du serveur MailChimp
     */
    public function executeCurlRequest(string $target, string $type, ?string $postfields = '') : ?array {

        $curl = curl_init();

        $data = array(
            CURLOPT_URL => $this->_url . $target,
            CURLOPT_CUSTOMREQUEST => $type,
            CURLOPT_USERPWD => 'user:' . $this->_apiKey,
            CURLOPT_HTTPHEADER => array( 'content-type: application/json' ),
            CURLOPT_TIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => $postfields
        );

        curl_setopt_array($curl, $data);
        
        $data = curl_exec($curl);

        curl_close($curl);

        return json_decode($data, true);
    }
}