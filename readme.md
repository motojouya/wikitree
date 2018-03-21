
# Wikitree

## �@�\
�����artisan�R�}���h�݂̂̎����B�ڍׂ͈ȉ��Q�ƁB
[artisan�R�}���h](app/Console/Commands/readme.md)

## SetUp

1. �܂��A�f�B���N�g����؂��āA�{�v���W�F�N�g��clone���܂��B
```
mkdir develop
cd develop
git clone https://github.com/motojouya/wikitree.git
```

2. ����LaraDock��clone���܂��B
```
git clone https://github.com/LaraDock/laradock.git
```

��Docker Tool box�̕��͈ȉ��̃u�����`���擾���Ă��������B
```
git clone -b LaraDock-ToolBox https://github.com/LaraDock/laradock.git
```

3. 2��clone����laradock�f�B���N�g���ɓ���ݒ�����������܂��B
```
cd laradock/
cp env-example .env
vi .env
```

```.env
APPLICATION=../wikitree/
```

4. docker-compose�Ńr���h���Aworkspace�ɓ���܂��B
```
docker-compose up -d workspace
docker-compose exec workspace bash
```

5. �R���e�i����composer����K�v���W���[�����C���X�g�[��
```
composer install
```

