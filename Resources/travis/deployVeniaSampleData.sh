#!/usr/bin/env bash
add_composer_repository () {
    name=$1
    type=$2
    url=$3
    echo "adding composer repository ${url}"
    ${composer} config ${composerParams} repositories.${name} ${type} ${url}
}

add_venia_sample_data_repository () {
    name=$1
    git clone https://github.com/PMET-public/${name}.git "$TRAVIS_BUILD_DIR/data/PMET-public/${name}"
    add_composer_repository ${name} git "$TRAVIS_BUILD_DIR/data/PMET-public/${name}"
}

execute_install () {
  composer='/usr/bin/env composer'
  composerParams='--no-interaction --ansi'
  moduleVendor='magento'
  moduleList=(
      module-catalog-sample-data-venia
      module-configurable-sample-data-venia
      sample-data-media-venia
  )
  githubBaseUrl='https://github.com/PMET-public'

  cd $install_path

  for moduleName in "${moduleList[@]}"
  do
     add_venia_sample_data_repository ${moduleName}
  done

  ${composer} require ${composerParams} $(printf "${moduleVendor}/%s:dev-master@dev " "${moduleList[@]}") --no-update
  for i in $(seq 1 3); do ${composer} update && s=0 && break || s=$? && sleep 1; done; (exit $s)
}

install_path=./

execute_install
