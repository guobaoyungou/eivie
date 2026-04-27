console.log('Step 1: Setting env vars');
process.env.NODE_ENV = 'production';
process.env.UNI_PLATFORM = 'h5';
process.env.UNI_INPUT_DIR = '/home/www/ai.eivie.cn/uniapp';
process.env.UNI_CLI_CONTEXT = '/home/www/ai.eivie.cn/uniapp';

try {
  console.log('Step 2: Loading Service');
  const Service = require('@vue/cli-service/lib/Service');
  console.log('Step 3: Creating service');
  const s = new Service(process.cwd());
  console.log('Step 4: Initializing');
  s.init('uni-build');
  console.log('Step 5: Registered commands:', Object.keys(s.commands));
  console.log('UNI_PLATFORM:', process.env.UNI_PLATFORM);
  console.log('UNI_INPUT_DIR:', process.env.UNI_INPUT_DIR);
  console.log('UNI_OUTPUT_DIR:', process.env.UNI_OUTPUT_DIR);
  
  console.log('Step 6: Running uni-build');
  s.run('uni-build').then(() => {
    console.log('BUILD DONE');
  }).catch(e => {
    console.error('BUILD ERROR:', e.message);
    console.error(e.stack);
  });
} catch(e) {
  console.error('CRASH:', e.message);
  console.error(e.stack);
}
