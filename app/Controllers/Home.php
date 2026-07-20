<?php

namespace App\Controllers;

use CI4FormBuilder\Form;
use CI4FormBuilder\Input;
use CI4FormBuilder\Label;
use CI4FormBuilder\Password;
use CI4FormBuilder\Submit;
use CI4FormBuilder\Dropdown;
use CI4FormBuilder\Checkbox;

class Home extends BaseController
{
    public function index(): string
    {
        $form = new Form(['action' => base_url('users/register'), 'method' => 'post']);

        $firstNameField = new Input('first_name');
        $firstNameField->setLabel(new Label('Prénom : ', 'first_name'));

        $lastNameField = new Input('last_name');
        $lastNameField->setLabel(new Label('Nom : ', 'last_name'));

        $emailField = new Input('email', '', '', 'email');
        $emailField->setLabel(new Label('Email : ', 'email'));

        $phoneField = new Input('phone');
        $phoneField->setLabel(new Label('Téléphone : ', 'phone'));

        $passwordField = new Password('password');
        $passwordField->setLabel(new Label('Mot de passe : ', 'password'));

        $terms = new Checkbox('terms', '1');
        $terms->setLabel(new Label('J\'accepte les conditions d\'utilisation', 'terms'));

        $submitButton = new Submit('btnRegister', 'S\'inscrire');

        $form->addComponent([
            $firstNameField,
            $lastNameField,
            $emailField,
            $phoneField,
            $passwordField,
            $terms,
            $submitButton
        ]);

        return view('form_example', [
            'form' => $form
        ]);
    }
}
