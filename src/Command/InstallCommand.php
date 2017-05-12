<?php

namespace HoLyCoWzOrZ\MyBB\Installer\Console\Command;

use GuzzleHttp\Client;
use PclZip;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

require_once(__DIR__.'/../pclzip.lib.php');

class InstallCommand extends Command
{
    protected $mybb_releases = [];
    protected $version_to_install;

    /*
     * Console Command Methods ********************
     */

    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Installs a new instance of MyBB')

            ->addArgument('dir', InputArgument::OPTIONAL, 'Name of folder where MyBB will be installed')
            ->addOption('ver', null, InputOption::VALUE_OPTIONAL, "The version of MyBB you would like to install", 'latest')
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Installs the latest MyBB 2.0 (Development) release');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $install_dir = ($input->getArgument('dir')) ? getcwd().'/'.$input->getArgument('dir') : getcwd();

        $this->getVersion($input);

        if (is_null($this->version_to_install)) {
            $output->writeln("\n".'<error>*** Bad version or version does not exist on GitHub. ***</error>' . "\n");

            $array_of_versions = [];
            foreach ($this->mybb_releases as $mybb_release) {
                $array_of_versions[] = $mybb_release['version'];
            }

            $helper = $this->getHelper('question');
            $version_choice_question = new ChoiceQuestion(
                'Available versions on GitHub (defaults to latest):',
                $array_of_versions,
                0
            );
            $version_choice_question->setErrorMessage('Option %s is an invalid option. Please try again.');
            $version_choice = $helper->ask($input, $output, $version_choice_question);

            foreach ($this->mybb_releases as $mybb_release) {
                if ($version_choice == $mybb_release['version']) {
                    $this->version_to_install = $mybb_release;
                }
            }

        }

        if ($this->version_to_install['version'] == '2.0') {
            $helper = $this->getHelper('question');

            $output->writeln('<fg=white;bg=red;options=bold>*** WARNING *** - MyBB 2.0 is under active development. It should not be installed on live boards.</>');
            $output->writeln('');
            $question = new ConfirmationQuestion('<info>Are you sure you want to install MyBB 2.0? </info>', false);

            if (!$helper->ask($input, $output, $question)) {
                return;
            }
        }

        $output->writeln('Installing MyBB <info>'.$this->version_to_install['version'].'</info> into: <info>'.$install_dir.'</info>');

        $this->download($zipFile = $this->makeFilename(), $this->version_to_install)
            ->extract($zipFile, $install_dir)
            ->cleanUp($zipFile);
    }

    protected function getVersion($input)
    {
        if ($input->getOption('dev')) {
            $this->version_to_install =  [
                'release' => 'dev',
                'version' => '2.0',
                'download' => 'https://github.com/mybb/mybb2/archive/master.zip'
            ];
        } else {
            $this->generateGitHubListOfReleases();

            if ($input->getOption('ver') == 'latest') {
                $this->version_to_install = $this->mybb_releases[0];
            } else {
                foreach ($this->mybb_releases as $mybb_release) {
                    if ($mybb_release['version'] == $input->getOption('ver')) {
                        $this->version_to_install = $mybb_release;
                    }
                }
            }
        }
    }

    protected function generateGitHubListOfReleases()
    {
        $client = new Client([
            'base_uri' => 'https://api.github.com',
            'timeout' => 3.0
        ]);

        $response = $client->request('GET','repos/mybb/mybb/tags');
        $json_data = json_decode($response->getBody()->getContents(), true);

        foreach ($json_data as $index => $json_datum) {
            $this->mybb_releases[$index]['release'] = $json_datum['name'];
            $this->mybb_releases[$index]['version'] = $this->versionize($json_datum['name']);
            $this->mybb_releases[$index]['download'] = $json_datum['zipball_url'];
        }
    }

    protected function versionize($version)
    {
        $pattern = '/[^(\d)]+(\d){1}(\d){1}(\d{2})/';
        $replacement = '$1.$2.$3';
        $readableVersion = preg_replace($pattern, $replacement, $version);

        return $this->stripLeadingZeros($readableVersion);
    }

    protected function stripLeadingZeros($string)
    {
        // Normalize build version so that zero-lead numbers
        // will be output as just single-digit numbers
        // 'A.B.CD' will become ['A', 'B', 'CD']
        // if C == 0, then CD will become D
        $array = explode('.', $string);

        $array[2] = (int) $array[2];
        $array[2] = (string)$array[2];

        $string = implode(".", $array);

        return $string;
    }

    /**
     * Generate a random temporary filename.
     *
     * @return string
     */
    protected function makeFilename()
    {
        return getcwd().'/mybb_'.md5(time().uniqid()).'.zip';
    }

    /**
     * Download the temporary Zip to the given file.
     *
     * @param  string  $zipFile
     * @param  string  $release
     * @return $this
     */
    protected function download($zipFile, $release)
    {
        $response = (new Client)->get($release['download']);
        file_put_contents($zipFile, $response->getBody());
        return $this;
    }

    /**
     * Extract the Zip file into the given directory.
     *
     * @param  string  $zipFile
     * @param  string  $directory
     * @return $this
     */
    protected function extract($zipFile, $directory)
    {
        $archive = new PclZip($zipFile);
        $list = $archive->listContent();

        if ($archive->extract(PCLZIP_OPT_PATH, $directory,
                PCLZIP_OPT_REMOVE_PATH, $list[0]['filename']) == 0) {
            die("Error : ".$archive->errorInfo(true));
        }

        return $this;
    }

    /**
     * Clean-up the Zip file.
     *
     * @param  string  $zipFile
     * @return $this
     */
    protected function cleanUp($zipFile)
    {
        @chmod($zipFile, 0777);
        @unlink($zipFile);
        return $this;
    }
}
