<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Hr\PublicHoliday;
use Illuminate\Database\Seeder;

class TanzaniaPublicHolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(?Company $company = null, ?int $year = null): void
    {
        \Log::info('TanzaniaPublicHolidaySeeder::run() called', [
            'company_id' => $company?->id,
            'company_name' => $company?->name,
            'year' => $year,
            'has_company' => !is_null($company),
            'has_year' => !is_null($year)
        ]);

        if ($company && $year) {
            // Seed for specific company and year
            \Log::info('Seeding for specific company and year', [
                'company_id' => $company->id,
                'year' => $year
            ]);
            $this->seedTanzaniaHolidays($company, $year);
        } else {
            // Default behavior: seed for all companies
            \Log::info('Seeding for all companies (default behavior)');
            $companies = Company::all();
            $currentYear = now()->year;

            foreach ($companies as $company) {
                $this->seedTanzaniaHolidays($company, $currentYear);
                $this->seedTanzaniaHolidays($company, $currentYear + 1); // Next year
            }
        }
        
        \Log::info('TanzaniaPublicHolidaySeeder::run() completed');
    }

    /**
     * Seed Tanzania public holidays
     */
    protected function seedTanzaniaHolidays(Company $company, int $year): void
    {
        \Log::info('seedTanzaniaHolidays() called', [
            'company_id' => $company->id,
            'year' => $year
        ]);

        $holidays = [
            [
                'date' => "$year-01-01",
                'name' => "New Year's Day",
                'description' => 'First day of the year',
                'recurring' => true,
            ],
            [
                'date' => "$year-01-12",
                'name' => 'Zanzibar Revolution Day',
                'description' => 'Anniversary of the 1964 Zanzibar Revolution',
                'recurring' => true,
            ],
            [
                'date' => "$year-03-29",
                'name' => 'Good Friday',
                'description' => 'Christian holy day commemorating the crucifixion of Jesus',
                'recurring' => false, // Date varies each year
            ],
            [
                'date' => "$year-04-01",
                'name' => 'Easter Monday',
                'description' => 'Day after Easter Sunday',
                'recurring' => false, // Date varies each year
            ],
            [
                'date' => "$year-04-07",
                'name' => 'Karume Day',
                'description' => "Commemoration of Abeid Amani Karume's assassination",
                'recurring' => true,
            ],
            [
                'date' => "$year-04-26",
                'name' => 'Union Day',
                'description' => 'Anniversary of the union between Tanganyika and Zanzibar',
                'recurring' => true,
            ],
            [
                'date' => "$year-05-01",
                'name' => "Workers' Day (Labour Day)",
                'description' => 'International Workers Day',
                'recurring' => true,
            ],
            [
                'date' => "$year-07-07",
                'name' => "Peasants' Day (Saba Saba)",
                'description' => 'Celebration of peasants and farmers',
                'recurring' => true,
            ],
            [
                'date' => "$year-08-08",
                'name' => "Nane Nane (Farmers' Day)",
                'description' => 'Day to honor farmers and agriculture',
                'recurring' => true,
            ],
            [
                'date' => "$year-10-14",
                'name' => "Mwalimu Nyerere Day",
                'description' => 'Commemoration of Julius Nyerere',
                'recurring' => true,
            ],
            [
                'date' => "$year-12-09",
                'name' => 'Independence Day',
                'description' => 'Commemoration of independence from British rule (1961)',
                'recurring' => true,
            ],
            [
                'date' => "$year-12-25",
                'name' => 'Christmas Day',
                'description' => 'Christian celebration of the birth of Jesus',
                'recurring' => true,
            ],
            [
                'date' => "$year-12-26",
                'name' => 'Boxing Day',
                'description' => 'Day after Christmas',
                'recurring' => true,
            ],
        ];

        // Add Islamic holidays (dates vary based on lunar calendar)
        // These are approximate and should be updated each year
        $islamicHolidays = $this->getIslamicHolidays($year);
        $holidays = array_merge($holidays, $islamicHolidays);

        \Log::info('Holidays array prepared', [
            'company_id' => $company->id,
            'year' => $year,
            'total_holidays' => count($holidays),
            'islamic_holidays_count' => count($islamicHolidays)
        ]);

        $created = 0;
        $updated = 0;
        $errors = 0;

        foreach ($holidays as $index => $holidayData) {
            try {
                \Log::debug('Processing holiday', [
                    'index' => $index + 1,
                    'name' => $holidayData['name'],
                    'date' => $holidayData['date'],
                    'company_id' => $company->id
                ]);

                $holiday = PublicHoliday::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'branch_id' => null,
                        'date' => $holidayData['date'],
                    ],
                    [
                        'name' => $holidayData['name'],
                        'description' => $holidayData['description'] ?? null,
                        'half_day' => $holidayData['half_day'] ?? false,
                        'recurring' => $holidayData['recurring'],
                        'is_active' => true,
                    ]
                );

                if ($holiday->wasRecentlyCreated) {
                    $created++;
                    \Log::debug('Holiday created', [
                        'id' => $holiday->id,
                        'name' => $holiday->name,
                        'date' => $holiday->date
                    ]);
                } else {
                    $updated++;
                    \Log::debug('Holiday updated', [
                        'id' => $holiday->id,
                        'name' => $holiday->name,
                        'date' => $holiday->date
                    ]);
                }
            } catch (\Exception $e) {
                $errors++;
                \Log::error("Failed to seed holiday", [
                    'name' => $holidayData['name'],
                    'date' => $holidayData['date'],
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                throw $e; // Re-throw to stop the process
            }
        }

        \Log::info('seedTanzaniaHolidays() completed', [
            'company_id' => $company->id,
            'year' => $year,
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
            'total_processed' => count($holidays)
        ]);
    }

    /**
     * Get Islamic holidays (approximate dates - should be verified annually)
     */
    protected function getIslamicHolidays(int $year): array
    {
        // Note: Islamic calendar dates vary and should be updated based on moon sighting
        // These are approximate Gregorian calendar equivalents

        $holidays = [];

        // Eid al-Fitr (approximate - varies by lunar calendar)
        if ($year == 2025) {
            $holidays[] = [
                'date' => '2025-03-31',
                'name' => 'Eid al-Fitr',
                'description' => 'Festival marking the end of Ramadan',
                'recurring' => false,
            ];
            $holidays[] = [
                'date' => '2025-04-01',
                'name' => 'Eid al-Fitr (Day 2)',
                'description' => 'Festival marking the end of Ramadan - Day 2',
                'recurring' => false,
            ];
        } elseif ($year == 2026) {
            $holidays[] = [
                'date' => '2026-03-20',
                'name' => 'Eid al-Fitr',
                'description' => 'Festival marking the end of Ramadan',
                'recurring' => false,
            ];
            $holidays[] = [
                'date' => '2026-03-21',
                'name' => 'Eid al-Fitr (Day 2)',
                'description' => 'Festival marking the end of Ramadan - Day 2',
                'recurring' => false,
            ];
        }

        // Eid al-Adha (approximate - varies by lunar calendar)
        if ($year == 2025) {
            $holidays[] = [
                'date' => '2025-06-07',
                'name' => 'Eid al-Adha',
                'description' => 'Festival of Sacrifice',
                'recurring' => false,
            ];
            $holidays[] = [
                'date' => '2025-06-08',
                'name' => 'Eid al-Adha (Day 2)',
                'description' => 'Festival of Sacrifice - Day 2',
                'recurring' => false,
            ];
        } elseif ($year == 2026) {
            $holidays[] = [
                'date' => '2026-05-27',
                'name' => 'Eid al-Adha',
                'description' => 'Festival of Sacrifice',
                'recurring' => false,
            ];
            $holidays[] = [
                'date' => '2026-05-28',
                'name' => 'Eid al-Adha (Day 2)',
                'description' => 'Festival of Sacrifice - Day 2',
                'recurring' => false,
            ];
        }

        // Maulid Day (Prophet's Birthday) - approximate
        if ($year == 2025) {
            $holidays[] = [
                'date' => '2025-09-05',
                'name' => 'Maulid Day',
                'description' => "Prophet Muhammad's Birthday",
                'recurring' => false,
            ];
        } elseif ($year == 2026) {
            $holidays[] = [
                'date' => '2026-08-25',
                'name' => 'Maulid Day',
                'description' => "Prophet Muhammad's Birthday",
                'recurring' => false,
            ];
        }

        return $holidays;
    }
}
