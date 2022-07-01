<?php

namespace App\Controller;
// namespace resolver;

use DateTime;
use App\Entity\Vehicule;
use App\Form\VehiculeType;
// use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class VehiculeController extends AbstractController
{
    /**
     * @Route("admin/vehicules", name="admin_app_vehicules")
     */
    public function adminVehicules(ManagerRegistry $doctrine): Response
    {
        $vehicules = $doctrine->getRepository(Vehicule::class)->findAll();

        return $this->render('vehicule/admin/adminVehicules.html.twig', [
            'vehicules' => $vehicules
        ]);
    }


    /**
     * @Route("/admin/vehicule-ajout", name="admin_ajout_vehicule")
     */
    public function ajout(Request $request, ManagerRegistry $doctrine, SluggerInterface $slugger)
    {
        $vehicule = new Vehicule();

        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {

            // on recupere l'image depuis le formulaire
            $file = $form->get('imageForm')->getData();
            //dd($file);
            //dd($vehicule);
            // le slug permet de modifier une chaine de caractéres : mot clé => mot-cle
            $fileName = $slugger->slug( $vehicule->getTitre() ) . uniqid() . '.' . $file->guessExtension();

            try{
                // on deplace le fichier image recuperé depuis le formulaire dans le dossier parametré dans la partie Parameters du fichier config/service.yaml, avec pour nom $fileName
                $file->move($this->getParameter('photos_vehicules'),  $fileName);
            }catch(FileException $e)
            
            {
                // gérer les exeptions en cas d'erreur durant l'upload
            }

            $vehicule->setPhoto($fileName);

            $vehicule->setDateEnregistrement(new DateTime("now"));

            $manager = $doctrine->getManager();
            $manager->persist($vehicule);
            $manager->flush();

            return $this->redirectToRoute("app_home");
        }
        
        return $this->render("vehicule/admin/formulaire.html.twig", [
            "formVehicule" => $form->createView()
        ]);

    }

    /**
     *@Route("/admin/update_vehicule/{id<\d+>}", name="admin_update_vehicule") 
     */
    public function update(ManagerRegistry $doctrine, $id, Request $request, SluggerInterface $slugger)
    {
        $vehicule = $doctrine->getRepository(Vehicule::class)->find($id);

        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->handleRequest($request);

        // on stock l'image du vehicule à mettre à jour
        $image = $vehicule->getPhoto();

        if($form->isSubmitted() && $form->isValid())
        {
            // si une image a bien été ajouté au formulaire
            if($form->get('imageForm')->getData() )
            {
                // on recupere l'image du formulaire
                $imageFile = $form->get('imageForm')->getData();
    
                //on crée un nouveau nom pour l'image
                $fileName = $slugger->slug($vehicule->getTitre()) . uniqid() . '.' . $imageFile->guessExtension();
    
                //on deplace l'image dans le dossier parametré dans service.yaml
                try{
                    $imageFile->move($this->getParameter('photos_vehicules'), $fileName);
                }catch(FileException $e){
                    // gestion des erreur upload
                }
                $vehicule->setPhoto($fileName);
                
            }
                $manager= $doctrine->getManager();
                $manager->persist($vehicule);
                $manager->flush();
                
                return $this->redirectToRoute('admin_app_vehicules');
        }

        return $this->render("vehicule/admin/formulaire.html.twig", [
            'formVehicule' => $form->createView()
        ]);
    }



}
