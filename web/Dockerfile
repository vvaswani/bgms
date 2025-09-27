FROM node:20-alpine

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm install

# Copy the rest of the application
COPY . .

# Add node_modules/.bin to PATH
ENV PATH /app/node_modules/.bin:$PATH

EXPOSE 5173

# Start the development server
CMD ["npm", "run", "dev"]
