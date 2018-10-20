FROM node:alpine

WORKDIR /data

COPY package.json .
COPY yarn.lock .

RUN yarn install

CMD ["yarn", "dev"]
