# Steps to use transaction with kubernetes
* enable Kubernetes on docker for windows if not already enabled (check settings)
* copy the env.kubernetes file inside the env file
* build transaction image based on dockerfile - `docker build -t purchase-gateway-image .`
* create a local namespace - `kubectl create namespace purchase-gateway`
* set current namespace as default one - `kubectl config set-context --current --namespace=purchase-gateway`
* add deployment to kubernetes - `kubectl apply -f kube.deployment.yml`
* add service to kubernetes - `kubectl apply -f kube.service.yml`
* add db service&deployment to kubernetes - `kubectl apply -f kube.db.yml`
* add jobs to kubernetes - `kubectl apply -f kube.job.yml`
* check if pods are running - `kubectl get pods`
* attach to pod - `kubectl exec -it purchase-gateway-ID --container purchase-gateway-php bash`
* once inside the container run composer install & doctrine:migrations:migrate

# Run in browser
* `localhost:8108`
