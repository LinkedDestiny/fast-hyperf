<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use LinkCloud\Fast\Hyperf\Constants\Time;
use LinkCloud\Fast\Hyperf\Helpers\JwtHelper;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class GenerateTokenCommand extends HyperfCommand
{
    /**
     * 执行的命令行
     */
    protected ?string $name = 'gen:token';

    protected ?string $signature = 'gen:token';

    public function handle()
    {
        $token = JwtHelper::generateToken([
            'merchant_id'     => '1',
            'shop_id'         => '1',
            'shop_account_id' => '1',
            'token_flag'      => config('setting.token_flag'),
        ], config('setting.jwt_key'), Time::MONTH);
        $this->info($token);
    }

    protected function getArguments(): array
    {
        return [
            ['type', InputArgument::OPTIONAL, 'token的类型'],
        ];
    }

    public function configure()
    {
        parent::configure();
        $this->addUsage('--type shop');
        $this->setDescription('token生成工具');
    }
}