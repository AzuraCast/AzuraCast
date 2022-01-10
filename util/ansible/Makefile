separator = "********************************************************************************"

.PHONY: header
header:
	@echo "******************** AZURACAST ANSIBLE MAKEFILE *********************"
	@echo "HOSTNAME        `uname -n`"
	@echo "KERNEL RELEASE  `uname -r`"
	@echo "KERNEL VERSION  `uname -v`"
	@echo "PROCESSOR       `uname -m`"
	@echo $(separator)

.PHONY: help
help:
	@echo $(separator)
	@echo "`tput bold``tput smul`Galaxie Clans Ansible Toolkit`tput rmul``tput sgr0` v0.1"
	@echo $(separator)
	@echo ""
	@echo "  make prepare-debian"
	@echo "    => $(prepare-debian-desc)"
	@echo ""
	@echo "  make env"
	@echo "    => $(env-desc)"
	@echo ""
	@echo "  make clean-env"
	@echo "    => $(clean-env-desc)"
	@echo ""
	@echo "  make doc"
	@echo "    => $(doc-desc)"
	@echo ""
	@echo "  make clean-doc"
	@echo "    => $(clean-doc-desc)"
	@echo $(separator)

.PHONY: env
env-desc = "Build local workspace environment"
env: header
	@echo ""
	@echo $(env-desc)
	@echo $(separator)
	@pip3 install -U pip --no-cache-dir --quiet &&\
	echo "[  OK  ] PIP3" || \
	echo "[FAILED] PIP3"

	@pip3 install -U wheel --no-cache-dir --quiet &&\
	echo "[  OK  ] WHEEL" || \
	echo "[FAILED] WHEEL"

	@pip3 install -U setuptools --no-cache-dir --quiet &&\
	echo "[  OK  ] SETUPTOOLS" || \
	echo "[FAILED] SETUPTOOLS"

	@pip3 install -U --no-cache-dir --quiet -r ${PWD}/requirements.txt &&\
	echo "[  OK  ] REQUIREMENTS" || \
	echo "[FAILED] REQUIREMENTS"

	@ echo ""
	@ echo "************************ IMPORT EXTERNAL ANSIBLE ROLES ************************"
	ansible-galaxy collection install -fr requirements.yml

