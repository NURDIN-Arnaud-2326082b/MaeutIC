<?php

namespace App\Form;

use App\Entity\Author;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Tag;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class AuthorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $countries = [
            'Afghanistan' => 'af',
            'Afrique du Sud' => 'za',
            'Albanie' => 'al',
            'Algérie' => 'dz',
            'Allemagne' => 'de',
            'Andorre' => 'ad',
            'Angola' => 'ao',
            'Arabie Saoudite' => 'sa',
            'Argentine' => 'ar',
            'Arménie' => 'am',
            'Australie' => 'au',
            'Autriche' => 'at',
            'Azerbaïdjan' => 'az',
            'Bahamas' => 'bs',
            'Bahreïn' => 'bh',
            'Bangladesh' => 'bd',
            'Barbade' => 'bb',
            'Belgique' => 'be',
            'Bénin' => 'bj',
            'Bhoutan' => 'bt',
            'Biélorussie' => 'by',
            'Birmanie' => 'mm',
            'Bolivie' => 'bo',
            'Bosnie-Herzégovine' => 'ba',
            'Botswana' => 'bw',
            'Brésil' => 'br',
            'Brunei' => 'bn',
            'Bulgarie' => 'bg',
            'Burkina Faso' => 'bf',
            'Burundi' => 'bi',
            'Cambodge' => 'kh',
            'Cameroun' => 'cm',
            'Canada' => 'ca',
            'Cap-Vert' => 'cv',
            'Chili' => 'cl',
            'Chine' => 'cn',
            'Chypre' => 'cy',
            'Colombie' => 'co',
            'Comores' => 'km',
            'Congo (Brazzaville)' => 'cg',
            'Congo (Kinshasa)' => 'cd',
            'Corée du Nord' => 'kp',
            'Corée du Sud' => 'kr',
            'Costa Rica' => 'cr',
            'Côte d\'Ivoire' => 'ci',
            'Croatie' => 'hr',
            'Cuba' => 'cu',
            'Danemark' => 'dk',
            'Djibouti' => 'dj',
            'Dominique' => 'dm',
            'Égypte' => 'eg',
            'Émirats arabes unis' => 'ae',
            'Équateur' => 'ec',
            'Érythrée' => 'er',
            'Espagne' => 'es',
            'Estonie' => 'ee',
            'Eswatini' => 'sz',
            'États-Unis' => 'us',
            'Éthiopie' => 'et',
            'Fidji' => 'fj',
            'Finlande' => 'fi',
            'France' => 'fr',
            'Gabon' => 'ga',
            'Gambie' => 'gm',
            'Géorgie' => 'ge',
            'Ghana' => 'gh',
            'Grèce' => 'gr',
            'Grenade' => 'gd',
            'Guatemala' => 'gt',
            'Guinée' => 'gn',
            'Guinée-Bissau' => 'gw',
            'Guinée équatoriale' => 'gq',
            'Guyana' => 'gy',
            'Haïti' => 'ht',
            'Honduras' => 'hn',
            'Hongrie' => 'hu',
            'Inde' => 'in',
            'Indonésie' => 'id',
            'Irak' => 'iq',
            'Iran' => 'ir',
            'Irlande' => 'ie',
            'Islande' => 'is',
            'Israël' => 'il',
            'Italie' => 'it',
            'Jamaïque' => 'jm',
            'Japon' => 'jp',
            'Jordanie' => 'jo',
            'Kazakhstan' => 'kz',
            'Kenya' => 'ke',
            'Kirghizistan' => 'kg',
            'Kiribati' => 'ki',
            'Koweït' => 'kw',
            'Laos' => 'la',
            'Lesotho' => 'ls',
            'Lettonie' => 'lv',
            'Liban' => 'lb',
            'Libéria' => 'lr',
            'Libye' => 'ly',
            'Liechtenstein' => 'li',
            'Lituanie' => 'lt',
            'Luxembourg' => 'lu',
            'Macédoine du Nord' => 'mk',
            'Madagascar' => 'mg',
            'Malaisie' => 'my',
            'Malawi' => 'mw',
            'Maldives' => 'mv',
            'Mali' => 'ml',
            'Malte' => 'mt',
            'Maroc' => 'ma',
            'Marshall' => 'mh',
            'Maurice' => 'mu',
            'Mauritanie' => 'mr',
            'Mexique' => 'mx',
            'Micronésie' => 'fm',
            'Moldavie' => 'md',
            'Monaco' => 'mc',
            'Mongolie' => 'mn',
            'Monténégro' => 'me',
            'Mozambique' => 'mz',
            'Namibie' => 'na',
            'Nauru' => 'nr',
            'Népal' => 'np',
            'Nicaragua' => 'ni',
            'Niger' => 'ne',
            'Nigéria' => 'ng',
            'Norvège' => 'no',
            'Nouvelle-Zélande' => 'nz',
            'Oman' => 'om',
            'Ouganda' => 'ug',
            'Ouzbékistan' => 'uz',
            'Pakistan' => 'pk',
            'Palaos' => 'pw',
            'Palestine' => 'ps',
            'Panama' => 'pa',
            'Papouasie-Nouvelle-Guinée' => 'pg',
            'Paraguay' => 'py',
            'Pays-Bas' => 'nl',
            'Pérou' => 'pe',
            'Philippines' => 'ph',
            'Pologne' => 'pl',
            'Portugal' => 'pt',
            'Qatar' => 'qa',
            'Roumanie' => 'ro',
            'Royaume-Uni' => 'gb',
            'Russie' => 'ru',
            'Rwanda' => 'rw',
            'Saint-Christophe-et-Niévès' => 'kn',
            'Saint-Marin' => 'sm',
            'Saint-Vincent-et-les-Grenadines' => 'vc',
            'Sainte-Lucie' => 'lc',
            'Salomon' => 'sb',
            'Salvador' => 'sv',
            'Samoa' => 'ws',
            'Sao Tomé-et-Principe' => 'st',
            'Sénégal' => 'sn',
            'Serbie' => 'rs',
            'Seychelles' => 'sc',
            'Sierra Leone' => 'sl',
            'Singapour' => 'sg',
            'Slovaquie' => 'sk',
            'Slovénie' => 'si',
            'Somalie' => 'so',
            'Soudan' => 'sd',
            'Soudan du Sud' => 'ss',
            'Sri Lanka' => 'lk',
            'Suède' => 'se',
            'Suisse' => 'ch',
            'Suriname' => 'sr',
            'Syrie' => 'sy',
            'Tadjikistan' => 'tj',
            'Tanzanie' => 'tz',
            'Tchad' => 'td',
            'Tchéquie' => 'cz',
            'Thaïlande' => 'th',
            'Timor oriental' => 'tl',
            'Togo' => 'tg',
            'Tonga' => 'to',
            'Trinité-et-Tobago' => 'tt',
            'Tunisie' => 'tn',
            'Turkménistan' => 'tm',
            'Turquie' => 'tr',
            'Tuvalu' => 'tv',
            'Ukraine' => 'ua',
            'Uruguay' => 'uy',
            'Vanuatu' => 'vu',
            'Vatican' => 'va',
            'Venezuela' => 've',
            'Viêt Nam' => 'vn',
            'Yémen' => 'ye',
            'Zambie' => 'zm',
            'Zimbabwe' => 'zw',
        ];
        asort($countries);

        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'required' => true,
            ])
            ->add('birthYear', IntegerType::class, [
                'label' => 'Année de naissance',
                'required' => true,
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\Range([
                        'max' => date('Y'),
                        'notInRangeMessage' => 'L\'année de naissance ne peut pas être supérieure à {{ max }}.',
                    ]),
                ],
            ])
            ->add('deathYear', IntegerType::class, [
                'label' => 'Année de décès',
                'required' => false,
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\Range([
                        'max' => date('Y'),
                        'notInRangeMessage' => 'L\'année de décès ne peut pas être supérieure à {{ max }}.',
                    ]),
                ],
            ])
            ->add('nationality', ChoiceType::class, [
                'label' => "Nationalité",
                'required' => true,
                'choices' => $countries,
                'placeholder' => 'Sélectionnez un pays',
                'attr' => [
                    'class' => 'bg-white mt-1 block w-full border border-gray-300 rounded-md p-2'
                ]
            ])
            ->add('link', TextType::class, [
                'label' => "Lien d'une biographie de l'auteur",
                'required' => true,
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Photo de l\'auteur',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\File([
                        'maxSize' => '4M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Merci d\'uploader une image valide (JPEG, PNG, WEBP)',
                    ])
                ],
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'mapped' => false,
                'label' => 'Mots-clés (tags)',
                'attr' => [
                    'class' => 'w-full p-2 border border-gray-300 rounded'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Author::class,
        ]);
    }
}
