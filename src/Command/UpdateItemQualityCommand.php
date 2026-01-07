<?php

namespace App\Command;

use App\Repository\ItemEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-item-quality',
    description: 'Met à jour la qualité des items en fonction de leur externalId',
)]
class UpdateItemQualityCommand extends Command
{
    public function __construct(
        private ItemEntityRepository $itemRepository,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $items = $this->itemRepository->findAll();
        $updated = 0;
        
        $io->progressStart(count($items));
        
        foreach ($items as $item) {
            $externalId = strtolower($item->getExternalId());
            $oldQuality = $item->getQuality();
            $newQuality = 'common'; // Valeur par défaut
            
            if (str_contains($externalId, 'rare')) {
                $newQuality = 'rare';
            } elseif (str_contains($externalId, 'uncommon')) {
                $newQuality = 'uncommon';
            }
            
            if ($oldQuality !== $newQuality) {
                $item->setQuality($newQuality);
                $updated++;
            }
            
            $io->progressAdvance();
        }
        
        $this->em->flush();
        $io->progressFinish();
        
        $io->success(sprintf(
            'Qualité mise à jour pour %d items sur %d.',
            $updated,
            count($items)
        ));

        return Command::SUCCESS;
    }
}
