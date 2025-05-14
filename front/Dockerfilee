FROM node:20 AS build-stage

WORKDIR /app

COPY package*.json ./

RUN npm ci

COPY ./ ./

RUN npm run build --prod

FROM nginx:1.25-alpine

RUN mkdir -p /app

COPY --from=build-stage /app/dist/front/browser /app

COPY ./.docker/angular/nginx.conf /etc/nginx/nginx.conf

USER nginx
