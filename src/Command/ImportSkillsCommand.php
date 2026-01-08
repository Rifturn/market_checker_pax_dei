<?php

namespace App\Command;

use App\Entity\Skill;
use App\Repository\SkillRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:import-skills',
    description: 'Importe les compétences (skills) depuis l\'API Gaming Tools',
)]
class ImportSkillsCommand extends Command
{
    public function __construct(
        private SkillRepository $skillRepository,
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $httpClient,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Import des Skills depuis l\'API');

        // URL de l'API
        $apiUrl = 'https://data-cdn.gaming.tools/paxdei/data/fr/skill.json?version=1767546126039';

        try {
            // Récupérer les données de l'API
            $io->text('Récupération des données depuis l\'API...');
            $response = $this->httpClient->request('GET', $apiUrl);
            $skills = $response->toArray();

            $io->info(sprintf('Nombre de skills trouvés : %d', count($skills)));

            $createdCount = 0;
            $updatedCount = 0;

            foreach ($skills as $skillData) {
                $externalId = $skillData['id'];
                
                // Chercher si le skill existe déjà
                $skill = $this->skillRepository->findByExternalId($externalId);
                
                if (!$skill) {
                    $skill = new Skill();
                    $skill->setExternalId($externalId);
                    $createdCount++;
                } else {
                    $updatedCount++;
                }

                // Mise à jour des données
                $skill->setName($skillData['name']);
                $skill->setUiGroup($skillData['uiGroup']);
                $skill->setSkillLevelCap($skillData['skillLevelCap']);
                $skill->setSkillBaseXp($skillData['skillBaseXp']);
                $skill->setEntityType($skillData['entityType']);
                $skill->setEntityTypeName($skillData['entityTypeName']);
                $skill->setListingPath($skillData['listingPath']);
                $skill->setCategoryIds($skillData['categoryIds'] ?? []);

                $this->entityManager->persist($skill);

                // Flush tous les 50 skills pour optimiser
                if (($createdCount + $updatedCount) % 50 === 0) {
                    $this->entityManager->flush();
                    $io->text(sprintf('  → Progression : %d skills traités', $createdCount + $updatedCount));
                }
            }

            // Flush final
            $this->entityManager->flush();

            $io->success(sprintf(
                'Import terminé ! Créés: %d | Mis à jour: %d | Total: %d',
                $createdCount,
                $updatedCount,
                count($skills)
            ));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error(sprintf('Erreur lors de l\'import : %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
