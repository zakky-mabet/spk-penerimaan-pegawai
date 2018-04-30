<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller 
{
  public function __construct()
  {
    parent::__construct();
    
    $this->load->library( array('session', 'form_validation', 'session','template'));

    $this->load->model('users', 'user');

    $this->load->helper(array('url'));
  }

  public function index()
  {

    $this->form_validation->set_rules('username', 'Username / Email', 'trim|required');
    $this->form_validation->set_rules('password', 'Password', 'trim|required');

    if ($this->form_validation->run() == TRUE)
    {
      $user = $this->user->get_admin_login();

      if( $user != FALSE)
      {
        if ( password_verify($this->input->post('password'), $user->password) ) 
        {
              $user_session = array(
                'rtlh_login' => TRUE,
                'ID' => $user->id_user,
                'ADMIN' => (Object) array(
                  'id_user' => $user->id_user,
                  'nama' => $user->nama,
                  'telepon' => $user->no_telp,
                  'email' => $user->email,
                  'alamat' => $user->alamat,
                  'username' => $user->username,
                  'level' => $user->level,

                )
              );  

              $this->session->set_userdata( $user_session );

          if( $this->input->post('from_url') != '' )
          {
            redirect($this->input->post('from_url'));
          } else {
            redirect(base_url('home'));
          }
        } else {
          $this->template->alert(
            ' Maaf! Kombinasi Username / E-Mail dengan password anda tidak valid.', 
            array('type' => 'danger','icon' => 'warning')
          );
        }
      } else {
        $this->template->alert(
          ' Maaf! Kombinasi Username / E-Mail dengan password anda tidak valid.', 
          array('type' => 'danger','icon' => 'warning')
        );
      }
    }

    $this->data = array(
      'title' => "Login Sistem Penerimaan Karyawan", 
    );

    $this->load->view('Kominfo/login', $this->data);
  }

  public function signout()
  {
    $this->session->sess_destroy();

    redirect(base_url("login?from_url=".$this->input->get('from_url')));
  }

  public function forgot()
  {
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');   
         
        if( $this->form_validation->run() == TRUE ) 
        {    
            $email = $this->input->post('email');   
            
            $clean = $this->security->xss_clean($email);  
            
            $userInfo =  $this->user->getUserLoginByEmail($clean); 
            
            if( ! $userInfo )
            {  
        $this->template->alert(
          'Email address salah, silakan coba lagi.', 
          array('type' => 'danger','icon' => 'warning')
        );

              redirect(site_url('login?action=lostpassword'),'refresh');   
            }  
                       
            $token = $this->user->insertToken($userInfo->ID);   

            $qstring = $this->base64url_encode($token);  


            $this->template->alert(
               // 'Permintaan Reset Password telah dikirim ke E-Mail anda. segera reset password anda sebelum 1X24 jam dari sekarang', 
              anchor(site_url() . '/Login/reset_password/token/' . $qstring, 'DISINI'),
                array('type' => 'success','icon' => 'check')
            );

            redirect('login');

            exit;  
        } 
  }

    public function reset_password()  
    {  
        $token = $this->base64url_decode($this->uri->segment(4));       

        $cleanToken = $this->security->xss_clean($token);  

        $user_info = $this->user->isTokenValid($cleanToken);  

        if( ! $user_info ) 
        {  
      $this->template->alert(
        'Token tidak valid atau kadaluarsa', 
        array('type' => 'danger','icon' => 'times')
      );
 
          redirect(site_url('login'),'refresh');   
        }    

        $data = array(  
          'title'=> 'Halaman Reset Password',  
          'nama'=> $user_info->nama,   
          'email'=>$user_info->email,   
          'token'=>$this->base64url_encode($token)  
        );  
         
        $this->form_validation->set_rules('password', 'Password', 'required|min_length[5]');  
        $this->form_validation->set_rules('passconf', 'Password Confirmation', 'required|matches[password]');         
         
        if ($this->form_validation->run() == FALSE) 
        {    
          $this->load->view('Kominfo/v_reset_password', $data);  
        } else {  
                           
          $post = $this->input->post(NULL, TRUE);          
          
          $cleanPost = $this->security->xss_clean($post);          
          
          $hashed = password_hash($cleanPost['password'], PASSWORD_DEFAULT);          
          
          $cleanPost['password'] = $hashed;  
          
          $cleanPost['user_id'] = $user_info->ID;  
          
          unset($cleanPost['passconf']);      

          if(!$this->user->updatePassword($cleanPost)) 
          {  
          $this->template->alert(
            'Update password gagal.', 
            array('type' => 'danger','icon' => 'times')
          );
          } else {  
          $this->template->alert(
            'Password anda sudah diperbaharui. Silakan login.', 
            array('type' => 'success','icon' => 'check')
          );
          }  

          redirect(site_url('login'),'refresh');         
        }  
    } 

    public function base64url_encode($data) 
    {   
      return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');   
    }   
   
    public function base64url_decode($data) 
    {   
      return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));   
    }

 
    
}

