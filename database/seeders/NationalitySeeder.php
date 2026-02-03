<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Nationality;

class NationalitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
 public function run()
    {
        $nationalities = [
            'Afghan', 'Albanian', 'Algerian', 'Andorran', 'Angolan',
            'Antiguans', 'Argentine', 'Armenian', 'Australian', 'Austrian',
            'Azerbaijani', 'Bahamian', 'Bahraini', 'Bangladeshi', 'Barbadian',
            'Belarusian', 'Belgian', 'Belizean', 'Beninese', 'Bhutanese',
            'Bolivian', 'Bosnian', 'Brazilian', 'British', 'Bruneian',
            'Bulgarian', 'Burkinabe', 'Burmese', 'Burundian', 'Cambodian',
            'Cameroonian', 'Canadian', 'Cape Verdean', 'Central African',
            'Chadian', 'Chilean', 'Chinese', 'Colombian', 'Comoran',
            'Congolese (Congo-Brazzaville)', 'Congolese (Congo-Kinshasa)', 'Costa Rican',
            'Croatian', 'Cuban', 'Cypriot', 'Czech', 'Danish',
            'Djiboutian', 'Dominican (Dominica)', 'Dominican (Dominican Republic)',
            'Ecuadorean', 'Egyptian', 'Emirati', 'Equatorial Guinean',
            'Eritrean', 'Estonian', 'Ethiopian', 'Fijian', 'Finnish',
            'French', 'Gabonese', 'Gambian', 'Georgian', 'German',
            'Ghanaian', 'Greek', 'Grenadian', 'Guatemalan', 'Guinea-Bissauan',
            'Guinean', 'Guyanese', 'Haitian', 'Herzegovinian', 'Honduran',
            'Hungarian', 'I-Kiribati', 'Icelander', 'Indian', 'Indonesian',
            'Iranian', 'Iraqi', 'Irish', 'Israeli', 'Italian',
            'Ivorian', 'Jamaican', 'Japanese', 'Jordanian', 'Kazakhstani',
            'Kenyan', 'Kittian and Nevisian', 'Kuwaiti', 'Kyrgyz', 'Laotian',
            'Latvian', 'Lebanese', 'Liberian', 'Libyan', 'Liechtensteiner',
            'Lithuanian', 'Luxembourger', 'Macedonian', 'Malagasy', 'Malawian',
            'Malaysian', 'Maldivian', 'Malian', 'Maltese', 'Marshallese',
            'Mauritanian', 'Mauritian', 'Mexican', 'Micronesian', 'Moldovan',
            'Monacan', 'Mongolian', 'Montenegrin', 'Moroccan', 'Mosotho',
            'Motswana', 'Mozambican', 'Namibian', 'Nauruan', 'Nepalese',
            'New Zealander', 'Nicaraguan', 'Nigerian', 'Nigerien', 'North Korean',
            'Northern Irish', 'Norwegian', 'Omani', 'Pakistani', 'Palauan',
            'Panamanian', 'Papua New Guinean', 'Paraguayan', 'Peruvian',
            'Polish', 'Portuguese', 'Qatari', 'Romanian', 'Russian',
            'Rwandan', 'Saint Lucian', 'Salvadoran', 'Samoan', 'San Marinese',
            'Sao Tomean', 'Saudi', 'Scottish', 'Senegalese', 'Serbian',
            'Seychellois', 'Sierra Leonean', 'Singaporean', 'Slovakian',
            'Slovenian', 'Solomon Islander', 'Somali', 'South African',
            'South Korean', 'South Sudanese', 'Spanish', 'Sri Lankan',
            'Sudanese', 'Surinamer', 'Swazi', 'Swedish', 'Swiss',
            'Syrian', 'Taiwanese', 'Tajikistani', 'Tanzanian', 'Thai',
            'Togolese', 'Tongan', 'Trinidadian/Tobagonian', 'Tunisian',
            'Turkish', 'Tuvaluan', 'Ugandan', 'Ukrainian', 'Uruguayan',
            'Uzbekistani', 'Venezuelan', 'Vietnamese', 'Yemeni',
            'Zambian', 'Zimbabwean',
            // you can add additional “nationalities” here if needed
        ];

        foreach ($nationalities as $name) {
            Nationality::firstOrCreate(['name' => $name]);
        }
    }
}
