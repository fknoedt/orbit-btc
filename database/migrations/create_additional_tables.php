<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('currency', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('country');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->unique('code');
        });

        DB::table('currency')->insert(
                array(
                    [
                        'code' => 'AED', 'country' => 'United Arab Emirates', 'name' => 'Dirhams'
                    ],
                    [
                        'code' => 'AFN', 'country' => 'Afghanistan', 'name' => 'Afghanis'
                    ],
                    [
                        'code' => 'ALL', 'country' => 'Albania', 'name' => 'Leke'
                    ],
                    [
                        'code' => 'AMD', 'country' => 'Armenia', 'name' => 'Drams'
                    ],
                    [
                        'code' => 'ANG', 'country' => 'Bonaire, Curaçao, Saba, Sint Eustatius and Sint Maarten', 'name' => 'Guilders (also called Florins)'
                    ],
                    [
                        'code' => 'AOA', 'country' => 'Angola', 'name' => 'Kwanza'
                    ],
                    [
                        'code' => 'ARS', 'country' => 'Argentina', 'name' => 'Pesos'
                    ],
                    [
                        'code' => 'AUD', 'country' => 'Australia', 'name' => 'Dollars'
                    ],
                    [
                        'code' => 'AWG', 'country' => 'Aruba', 'name' => 'Guilders (also called Florins)'
                    ],
                    [
                        'code' => 'AZN', 'country' => 'Azerbaijan', 'name' => 'New Manats'
                    ],
                    [
                        'code' => 'BAM', 'country' => 'Bosnia and Herzegovina', 'name' => 'Convertible Marka'
                    ],
                    [
                        'code' => 'BBD', 'country' => 'Barbados', 'name' => 'Dollars'
                    ],
                    [
                        'code' => 'BDT', 'country' => 'Bangladesh', 'name' => 'Taka'
                    ],
                    [
                        'code' => 'BGN', 'country' => 'Bulgaria', 'name' => 'Leva'
                    ],
                    [
                        'code' => 'BHD', 'country' => 'Bahrain', 'name' => 'Dinars'
                    ],
                    [
                        'code' => 'BIF', 'country' => 'Burundi', 'name' => 'Francs'
                    ],
                    [
                        'code' => 'BMD', 'country' => 'Bermuda', 'name' => 'Dollars'
                    ],
                    [
                        'code' => 'BND', 'country' => 'Brunei Darussalam', 'name' => 'Dollars'
                    ],
                    [
                        'code' => 'BOB', 'country' => 'Bolivia', 'name' => 'Bolivianos'
                    ],
                    [
                        'code' => 'BRL', 'country' => 'Brazil', 'name' => 'Brazil Real'
                    ],
                    [
                        'code' => 'BSD', 'country' => 'Bahamas', 'name' => 'Dollars'
                    ],
                    [
                        'code' => 'BTN', 'country' => 'Bhutan', 'name' => 'Ngultrum'
                    ],
                    [
                        'code' => 'BWP', 'country' => 'Botswana', 'name' => 'Pulas'
                    ],
                    [
                        'code' => 'BYR', 'country' => 'Belarus', 'name' => 'Rubles'
                    ],
                    [
                        'code' => 'BZD', 'country' => 'Belize', 'name' => 'Dollars'
                    ],
                    [
                        'code' => 'CAD', 'country' => 'Canada', 'name' => 'Dollars'
                    ],
                    [
                        'code' => 'CDF', 'country' => 'Congo/Kinshasa', 'name' => 'Congolese Francs'
                    ],
                    [
                        'code' => 'CHF', 'country' => 'Switzerland', 'name' => 'Francs'
                    ],
                    [
                        'code' => 'CLP', 'country' => 'Chile', 'name' => 'Pesos'
                    ],
                    [
                        'code' => 'CNY', 'country' => 'China', 'name' => 'Yuan Renminbi'
                    ],
                    [
                        'code' => 'COP', 'country' => 'Columbia', 'name' => 'Pesos'
                    ],
                    [
                        'code' => 'CRC', 'country' => 'Costa Rica', 'name' => 'Colones'
                    ],
                    [
                        'code' => 'CUP', 'country' => 'Cuba', 'name' => 'Pesos'
                    ],
                    [
                        'code' => 'CVE', 'country' => 'Cabo Verde', 'name' => 'Escudos'
                    ],
                    [
                        'code' => 'CZK', 'country' => 'Czech Republic', 'name' => 'Koruny'
                    ],
                    [
                        'code' => 'DJF', 'country' => 'Djibouti', 'name' => 'Francs'
                    ],
                    [
                        'code' => 'DKK', 'country' => 'Denmark', 'name' => 'Kroner'
                    ],
                    [
                        'code' => 'DOP', 'country' => 'Dominican Republic', 'name' => 'Pesos'
                    ],
                    [
                        'code' => 'DZD', 'country' => 'Algeria', 'name' => 'Algeria Dinars'
                    ],
                    [
                        'code' => 'EEK', 'country' => 'Estonia', 'name' => 'Krooni'
                    ],
                    [
                        'code' => 'EGP', 'country' => 'Egypt', 'name' => 'Pounds'
                    ],
                    [
                        'code' => 'ERN', 'country' => 'Eritrea', 'name' => 'Nakfa'
                    ],
                    [
                        'code' => 'ETB', 'country' => 'Ethopia', 'name' => 'Birr'
                    ],
                    [
                        'code' => 'EUR', 'country' => 'Euro Member Countries', 'name' => 'Euro'
                    ],
                    [
                        'code' => 'FJD', 'country' => 'Fiji', 'name' => 'Dollars'
                    ],
                    [
                        'code' => 'FKP', 'country' => 'Falkland Islands', 'name' => 'Pounds'
                    ],
                    [
                        'code' => 'GBP', 'country' => 'United Kingdom', 'name' => 'Pounds'
                    ],
                    [
                        'code' => 'GEL', 'country' => 'Georgia', 'name' => 'Lari'
                    ],
                    [
                        'code' => 'GGP', 'country' => 'Guernsey', 'name' => 'Pounds'
                    ],
                    [
                        'code' => 'GHS', 'country' => 'Ghana', 'name' => 'Cedis'
                    ],
                    [
                        'code' => 'GIP', 'country' => 'Gibraltar', 'name' => 'Pounds'
                    ],
                    [
                        'code' => 'GMD', 'country' => 'Gambia', 'name' => 'Dalasi'
                    ],
                    [
                        'code' => 'GNF', 'country' => 'Guinea', 'name' => 'Francs'
                    ],
                    [
                        'code' => 'GTQ', 'country' => 'Guatemala', 'name' => 'Quetzales'
                    ],
                    [
                        'code' => 'GYD', 'country' => 'Guyana', 'name' => 'Dollars'
                    ],
                    [
                        'code' => 'HKD', 'country' => 'Hong Kong Special Administrative Region', 'name' => 'Dollars'
                    ],
                    [
                        'code' => 'HNL', 'country' => 'Honduras', 'name' => 'Lempiras'
                    ],
                    [
                        'code' => 'HRK', 'country' => 'Croatia', 'name' => 'Kuna'
                    ],
                    [
                        'code' => 'HTG', 'country' => 'Haiti', 'name' => 'Gourdes'
                    ],
                    [
                        'code' => 'HUF', 'country' => 'Hungary', 'name' => 'Forint'
                    ],
                    [
                        'code' => 'IDR', 'country' => 'Indonesia', 'name' => 'Rupiahs'
                    ],
                    [
                        'code' => 'ILS', 'country' => 'Israel', 'name' => 'New Shekels'
                    ],
                    [
                        'code' => 'IMP', 'country' => 'Isle of Man', 'name' => 'Pounds'
                    ],
                    [
                        'code' => 'INR', 'country' => 'India', 'name' => 'Rupees'
                    ],
                    [
                        'code' => 'IQD', 'country' => 'Iraq', 'name' => 'Dinars'
                    ],
                    [
                        'code' => 'IRR', 'country' => 'Iran', 'name' => 'Rials'
                    ],
                    [
                        'code' => 'ISK', 'country' => 'Iceland', 'name' => 'Kronur'
                    ],
                    [
                        'code' => 'JEP', 'country' => 'Jersey', 'name' => 'Pounds'
                    ],
                    [
                        'code' => 'JMD', 'country' => 'Jamaica', 'name' => 'Dollars'
                    ],
                    [
                        'code' => 'JOD', 'country' => 'Jordan', 'name' => 'Dinars'
                    ],
                    [
                        'code' => 'JPY', 'country' => 'Japan', 'name' => 'Yen'
                    ],
                    [
                        'code' => 'KES', 'country' => 'Kenya', 'name' => 'Shillings'
                    ],
                    [
                        'code' => 'KGS', 'country' => 'Kyrgyzstan', 'name' => 'Soms'
                    ],
                    [
                        'code' => 'KHR', 'country' => 'Cambodia', 'name' => 'Riels'
                    ],
                    [
                        'code' => 'KMF', 'country' => 'Comoros', 'name' => 'Francs'
                    ],
                    [
                        'code' => 'KPW', 'country' => 'Korea (North)', 'name' => 'Won'
                    ],
                    [
                        'code' => 'KRW', 'country' => 'Korea (South)', 'name' => 'Won'
                    ],
                    [
                        'code' => 'KWD', 'country' => 'Kuwait', 'name' => 'Dinars'
                    ],
                    [
                        'code' => 'KYD', 'country' => 'Cayman Islands', 'name' => 'Dollars'
                    ],
                    [
                        'code' => 'KZT', 'country' => 'Kazakhstan', 'name' => 'Tenge'
                    ],
                    [
                        'code' => 'LAK', 'country' => 'Laos', 'name' => 'Kips'
                    ],
                    [
                        'code' => 'LBP', 'country' => 'Lebanon', 'name' => 'Pounds'
                    ],
                    [
                        'code' => 'LKR', 'country' => 'Sri Lanka', 'name' => 'Rupees'
                    ],
                    [
                        'code' => 'LRD', 'country' => 'Liberia', 'name' => 'Dollars'
                    ],
                    [
                        'code' => 'LSL', 'country' => 'Lesotho', 'name' => 'Maloti'
                    ],
                    [
                        'code' => 'LTL', 'country' => 'Lithuania', 'name' => 'Litai'
                    ],
                    [
                        'code' => 'LVL', 'country' => 'Latvia', 'name' => 'Lati'
                    ],
                    [
                        'code' => 'LYD', 'country' => 'Libya', 'name' => 'Dinars'
                    ],
                    [
                        'code' => 'MAD', 'country' => 'Morocco', 'name' => 'Dirhams'
                    ],
                    [
                        'code' => 'MDL', 'country' => 'Moldova', 'name' => 'Dei'
                    ],
                    [
                        'code' => 'MGA', 'country' => 'Madagascar', 'name' => 'Ariary'
                    ],
                    [
                        'code' => 'MKD', 'country' => 'North Macedonia', 'name' => 'Denars'
                    ],
                    [
                        'code' => 'MMK', 'country' => 'Myanmar (Burma)', 'name' => 'Kyats'
                    ],
                    [
                        'code' => 'MNT', 'country' => 'Mongolia', 'name' => 'Tugriks'
                    ],
                    [
                        'code' => 'MOP', 'country' => 'Macao Special Administrative Region', 'name' => 'Patacas'
                    ],
                    [
                        'code' => 'MRO', 'country' => 'Mauritania', 'name' => 'Ouguiyas'
                    ],
                    [
                        'code' => 'MUR', 'country' => 'Mauritius', 'name' => 'Rupees'
                    ],
                    [
                        'code' => 'MVR', 'country' => 'Maldives (Maldive Islands)', 'name' => 'Rufiyaa'
                    ],
                    [
                        'code' => 'MWK', 'country' => 'Malawi', 'name' => 'Kwachas'
                    ],
                    [
                        'code' => 'MXN', 'country' => 'Mexico', 'name' => 'Pesos'
                    ],
                    [
                        'code' => 'MYR', 'country' => 'Malaysia', 'name' => 'Ringgits'
                    ],
                    [
                        'code' => 'MZN', 'country' => 'Mozambique', 'name' => 'Meticais'
                    ],
                    [
                        'code' => 'NAD', 'country' => 'Namibia', 'name' => 'Dollars'
                    ],
                    [
                        'code' => 'NGN', 'country' => 'Nigeria', 'name' => 'Nairas'
                    ],
                    [
                        'code' => 'NIO', 'country' => 'Nicaragua', 'name' => 'Cordobas'
                    ],
                    [
                        'code' => 'NOK', 'country' => 'Norway', 'name' => 'Krone'
                    ],
                    [
                        'code' => 'NPR', 'country' => 'Nepal', 'name' => 'Nepal Rupees'
                    ],
                    [
                        'code' => 'NZD', 'country' => 'New Zealand', 'name' => 'Dollars'
                    ],
                    [
                        'code' => 'OMR', 'country' => 'Oman', 'name' => 'Rials'
                    ],
                    [
                        'code' => 'PAB', 'country' => 'Panama', 'name' => 'Balboa'
                    ],
                    [
                        'code' => 'PEN', 'country' => 'Peru', 'name' => 'Nuevos Soles'
                    ],
                    [
                        'code' => 'PGK', 'country' => 'Papua New Guinea', 'name' => 'Kina'
                    ],
                    [
                        'code' => 'PHP', 'country' => 'Philippines', 'name' => 'Pesos'
                    ],
                    [
                        'code' => 'PKR', 'country' => 'Pakistan', 'name' => 'Rupees'
                    ],
                    [
                        'code' => 'PLN', 'country' => 'Poland', 'name' => 'Zlotych'
                    ],
                    [
                        'code' => 'PYG', 'country' => 'Paraguay', 'name' => 'Guarani'
                    ],
                    [
                        'code' => 'QAR', 'country' => 'Qatar', 'name' => 'Rials'
                    ],
                    [
                        'code' => 'RON', 'country' => 'Romania', 'name' => 'New Lei'
                    ],
                    [
                        'code' => 'RSD', 'country' => 'Serbia', 'name' => 'Dinars'
                    ],
                    [
                        'code' => 'RUB', 'country' => 'Russia', 'name' => 'Rubles'
                    ],
                    [
                        'code' => 'RWF', 'country' => 'Rwanda', 'name' => 'Rwanda Francs'
                    ],
                    [
                        'code' => 'SAR', 'country' => 'Saudi Arabia', 'name' => 'Riyals'
                    ],
                    [
                        'code' => 'SBD', 'country' => 'Solomon Islands', 'name' => 'Dollars'
                    ],
                    [
                        'code' => 'SCR', 'country' => 'Seychelles', 'name' => 'Rupees'
                    ],
                    [
                        'code' => 'SDG', 'country' => 'Sudan', 'name' => 'Pounds'
                    ],
                    [
                        'code' => 'SEK', 'country' => 'Sweden', 'name' => 'Kronor'
                    ],
                    [
                        'code' => 'SGD', 'country' => 'Singapore', 'name' => 'Dollars'
                    ],
                    [
                        'code' => 'SHP', 'country' => 'St Helena, Ascension, Tristan da Cunha', 'name' => 'Pounds'
                    ],
                    [
                        'code' => 'SKK', 'country' => 'Slovakia', 'name' => 'Koruny'
                    ],
                    [
                        'code' => 'SLL', 'country' => 'Sierra Leone', 'name' => 'Leones'
                    ],
                    [
                        'code' => 'SOS', 'country' => 'Somalia', 'name' => 'Shillings'
                    ],
                    [
                        'code' => 'SPL', 'country' => 'Seborga', 'name' => 'Luigini'
                    ],
                    [
                        'code' => 'SRD', 'country' => 'Suriname', 'name' => 'Dollars'
                    ],
                    [
                        'code' => 'STD', 'country' => 'São Tome and Principe', 'name' => 'Dobras'
                    ],
                    [
                        'code' => 'SVC', 'country' => 'El Salvador', 'name' => 'Colones'
                    ],
                    [
                        'code' => 'SYP', 'country' => 'Syria', 'name' => 'Pounds'
                    ],
                    [
                        'code' => 'SZL', 'country' => 'Swaziland', 'name' => 'Emalangeni'
                    ],
                    [
                        'code' => 'THB', 'country' => 'Thailand', 'name' => 'Baht'
                    ],
                    [
                        'code' => 'TJS', 'country' => 'Tajikistan', 'name' => 'Somoni'
                    ],
                    [
                        'code' => 'TMM', 'country' => 'Turkmenistan', 'name' => 'Manats'
                    ],
                    [
                        'code' => 'TND', 'country' => 'Tunisia', 'name' => 'Dinars'
                    ],
                    [
                        'code' => 'TOP', 'country' => 'Tonga', 'name' => 'Pa\'anga'
                    ],
                    [
                        'code' => 'TRY', 'country' => 'Türkiye', 'name' => 'New Lira'
                    ],
                    [
                        'code' => 'TTD', 'country' => 'Trinidad and Tobago', 'name' => 'Dollars'
                    ],
                    [
                        'code' => 'TVD', 'country' => 'Tuvalu', 'name' => 'Tuvalu Dollars'
                    ],
                    [
                        'code' => 'TWD', 'country' => 'Taiwan', 'name' => 'New Dollars'
                    ],
                    [
                        'code' => 'TZS', 'country' => 'Tanzania', 'name' => 'Shillings'
                    ],
                    [
                        'code' => 'UAH', 'country' => 'Ukraine', 'name' => 'Hryvnia'
                    ],
                    [
                        'code' => 'UGX', 'country' => 'Uganda', 'name' => 'Shillings'
                    ],
                    [
                        'code' => 'USD', 'country' => 'United States of America', 'name' => 'Dollars'
                    ],
                    [
                        'code' => 'UYU', 'country' => 'Uruguay', 'name' => 'Pesos'
                    ],
                    [
                        'code' => 'UZS', 'country' => 'Uzbekistan', 'name' => 'Sums'
                    ],
                    [
                        'code' => 'VEF', 'country' => 'Venezuela', 'name' => 'Bolivares Fuertes'
                    ],
                    [
                        'code' => 'VND', 'country' => 'Viet Nam', 'name' => 'Dong'
                    ],
                    [
                        'code' => 'VUV', 'country' => 'Vanuatu', 'name' => 'Vatu'
                    ],
                    [
                        'code' => 'WST', 'country' => 'Samoa', 'name' => 'Tala'
                    ],
                    [
                        'code' => 'XAF', 'country' => 'Communauté Financière Africaine BEAC', 'name' => 'Francs'
                    ],
                    [
                        'code' => 'XAG', 'country' => 'Silver', 'name' => 'Ounces'
                    ],
                    [
                        'code' => 'XAU', 'country' => 'Gold', 'name' => 'Ounces'
                    ],
                    [
                        'code' => 'XCD', 'country' => 'East Caribbean', 'name' => 'Dollars'
                    ],
                    [
                        'code' => 'XDR', 'country' => 'International Monetary Fund (IMF)', 'name' => 'Special Drawing Rights'
                    ],
                    [
                        'code' => 'XOF', 'country' => 'Communauté Financière Africaine BCEAO', 'name' => 'Francs'
                    ],
                    [
                        'code' => 'XPD', 'country' => 'Palladium', 'name' => 'Ounces'
                    ],
                    [
                        'code' => 'XPF', 'country' => 'Comptoirs Français du Pacifique', 'name' => 'Francs'
                    ],
                    [
                        'code' => 'XPT', 'country' => 'Platinum', 'name' => 'Ounces'
                    ],
                    [
                        'code' => 'YER', 'country' => 'Yemen', 'name' => 'Rials'
                    ],
                    [
                        'code' => 'ZAR', 'country' => 'South Africa', 'name' => 'Rand'
                    ],
                    [
                        'code' => 'ZMK', 'country' => 'Zambia', 'name' => 'Kwacha'
                    ],
                    [
                        'code' => 'ZWD', 'country' => 'Zimbabwe', 'name' => 'Zimbabwe Dollars'
                    ],
                )
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        //void
    }
};
